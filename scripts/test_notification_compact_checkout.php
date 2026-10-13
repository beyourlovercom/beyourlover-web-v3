<?php
/** C2: synthetic WordPress route gate tests, no Woo runtime or HTTP. */
declare(strict_types=1);
define('ABSPATH','/synthetic/');
$GLOBALS['host']='dev.beyourlover.com';
$GLOBALS['checkout']=$GLOBALS['pay']=$GLOBALS['received']=$GLOBALS['admin']=$GLOBALS['ajax']=false;
$GLOBALS['hooks']=[]; $GLOBALS['styles']=[];
function home_url(string $path='/'): string{return 'https://'.$GLOBALS['host'].$path;}
function wp_parse_url(string $url,int $component): string|false|null{return parse_url($url,$component);}
function wp_unslash($x){return $x;}
function is_checkout(): bool{return $GLOBALS['checkout'];}
function is_checkout_pay_page(): bool{return $GLOBALS['pay'];}
function is_order_received_page(): bool{return $GLOBALS['received'];}
function is_wc_endpoint_url(string $key): bool{return $key==='order-pay' && $GLOBALS['pay'];}
function is_admin(): bool{return $GLOBALS['admin'];}
function wp_doing_ajax(): bool{return $GLOBALS['ajax'];}
function add_action(string $name,callable $fn,int $priority=10): void{$GLOBALS['hooks']['a'][$name][]=$fn;}
function add_filter(string $name,callable $fn,int $priority=10): void{$GLOBALS['hooks']['f'][$name][]=$fn;}
function get_stylesheet_directory(): string{return dirname(__DIR__).'/theme/beyourlover-v3';}
function get_stylesheet_directory_uri(): string{return 'https://dev.beyourlover.com/wp-content/themes/beyourlover-v3';}
function wp_enqueue_style(string $handle,string $uri,array $deps=[],string $version=''): void{
    $GLOBALS['styles'][$handle]=['url'=>$uri,'deps'=>$deps,'version'=>$version];
}
require dirname(__DIR__).'/theme/beyourlover-v3/inc/notification-compact-checkout.php';
$reset=static function (): void{
    $GLOBALS['host']='dev.beyourlover.com';$GLOBALS['checkout']=true;
    $GLOBALS['pay']=$GLOBALS['received']=$GLOBALS['admin']=$GLOBALS['ajax']=false;
    $_SERVER['REQUEST_METHOD']='GET';$_GET=[];
};
$assert=static function(bool $expected,string $label): void{
    $actual=byl_v3_compact_checkout_preview_enabled();
    if($actual!==$expected){fwrite(STDERR,"C2_FAIL ".$label."\n");exit(1);}
};
$reset();$assert(false,'default OFF');
$_GET=['byl_compact_notice_preview'=>'checkout'];$assert(true,'explicit Checkout GET');
foreach($GLOBALS['hooks']['a']['wp_enqueue_scripts'] as $fn){$fn();}
if(count($GLOBALS['styles'])!==2
    || !isset($GLOBALS['styles']['byl-v3-compact-checkout'])
    || $GLOBALS['styles']['byl-v3-compact-checkout']['deps']!==['byl-v3-compact-persistent'])exit(2);
foreach($GLOBALS['hooks']['f']['body_class'] as $fn){$classes=$fn([]);}
if($classes!==['byl-cpn-checkout-preview'])exit(3);
$reset();$GLOBALS['checkout']=false;$_GET=['byl_compact_notice_preview'=>'checkout'];$assert(false,'not checkout');
$reset();$GLOBALS['pay']=true;$_GET=['byl_compact_notice_preview'=>'checkout'];$assert(false,'order-pay excluded');
$reset();$GLOBALS['received']=true;$_GET=['byl_compact_notice_preview'=>'checkout'];$assert(false,'thankyou excluded');
$reset();$_GET=['byl_compact_notice_preview'=>'cart'];$assert(false,'cart flag denied');
$reset();$_GET=['byl_compact_notice_preview'=>['checkout']];$assert(false,'array flag denied');
$reset();$_GET=['byl_compact_notice_preview'=>'checkout'];$GLOBALS['host']='www.beyourlover.com';$assert(false,'WWW denied');
$reset();$_GET=['byl_compact_notice_preview'=>'checkout'];$_SERVER['REQUEST_METHOD']='POST';$assert(false,'POST denied');
$reset();$_GET=['byl_compact_notice_preview'=>'checkout'];$GLOBALS['admin']=true;$assert(false,'admin denied');
$reset();$_GET=['byl_compact_notice_preview'=>'checkout'];$GLOBALS['ajax']=true;$assert(false,'Ajax denied');
echo "CPN_C2_CHECKOUT_ROUTE_GATE_PASS: default OFF, GET, Dev-only, no orderpay/thankyou\n";
