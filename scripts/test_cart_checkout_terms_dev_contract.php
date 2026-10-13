<?php
/** Isolated Dev-only Woo Classic Checkout consent handoff; never submits an order. */
declare(strict_types=1);
define('ABSPATH', '/mock');
$GLOBALS['host']='dev.beyourlover.com';
$GLOBALS['hooks']=[];
function home_url(string $path='/'): string { return 'https://'.$GLOBALS['host'].$path; }
function wp_parse_url(string $value,int $part=-1): string|false|null { return parse_url($value,$part); }
function add_filter(string $hook,callable $fn,int $priority=10,int $n=1): void {
    $GLOBALS['hooks'][$hook][]=$fn;
}
function add_action(string $hook,callable $fn,int $priority=10,int $n=1): void {}
function apply_filters(string $hook,$value){
    foreach($GLOBALS['hooks'][$hook]??[] as $fn)$value=$fn($value);
    return $value;
}
require_once __DIR__.'/../theme/beyourlover-v3/inc/checkout.php';
$N=0;$assert=static function(bool $ok,string $label)use(&$N):void{
    $N++;if(!$ok)throw new RuntimeException("CHECKOUT_TERMS_FAIL_".$label);
};
$assert(byl_v3_dev_checkout_terms_handoff_enabled(),'Dev host gate');
$assert(apply_filters('woocommerce_checkout_show_terms',true)===false,'Dev Woo terms template hidden');
$assert(apply_filters('woocommerce_get_terms_and_conditions_checkbox_text','Terms Link')==='',
    'Dev Woo checkbox text empty for classic checkout server validation');
$GLOBALS['host']='www.beyourlover.com';
$assert(!byl_v3_dev_checkout_terms_handoff_enabled(),'WWW host fails closed');
$assert(apply_filters('woocommerce_checkout_show_terms',true)===true,'WWW terms template preserved');
$assert(apply_filters('woocommerce_get_terms_and_conditions_checkbox_text','Terms Link')==='Terms Link',
    'WWW required checkbox server policy preserved');
$cart=file_get_contents(__DIR__.'/../theme/beyourlover-v3/inc/cart-drawer.php');
$assert(str_contains($cart,'data-drawer-checkout') && str_contains($cart,'Terms and Conditions'),
    'linked disclosure next to click-through action');
$assert(str_contains($cart,'woocommerce-payments/assets/images/cards/') &&
    str_contains($cart,'byl-drawer__payment-methods'),'reuse existing plugin payment logos');
$css=file_get_contents(__DIR__.'/../theme/beyourlover-v3/assets/css/cart-drawer.css');
$assert(str_contains($css,'byl-drawer__charges-notice') &&
    str_contains($css,'byl-drawer__terms') &&
    str_contains($css,'byl-drawer__payment-methods'),'mobile terms & warning CSS present');
echo "CART_CHECKOUT_TERMS_DEV_ONLY_CONTRACT_PASS={$N}; WWW_CHECKBOX_UNCHANGED=YES; ORDER_WRITES=0\n";
