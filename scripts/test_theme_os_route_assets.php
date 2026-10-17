<?php
/** Isolated T2.4 asset route parity: no WordPress runtime, orders, network or DB. */
declare(strict_types=1);
define('ABSPATH', __DIR__);
$GLOBALS['route'] = 'generic';
$GLOBALS['wc_checkout_registered'] = true;
$GLOBALS['actions'] = [];
function add_action(string $name, $callback, int $priority = 10): void {
    $GLOBALS['actions'][$name][] = ['callback' => $callback, 'priority' => $priority];
}
function get_stylesheet_directory(): string { return dirname(__DIR__).'/theme/beyourlover-v3'; }
function get_stylesheet_directory_uri(): string { return 'https://theme.local.test'; }
function get_stylesheet_uri(): string { return 'https://theme.local.test/style.css'; }
function wp_get_theme(): object { return new class { public function get($key) { return '0.1.0'; } }; }
function wp_enqueue_style($handle,$src,$deps=[],$version=null): void {
    $GLOBALS['styles'][$handle] = ['deps'=>$deps,'version'=>$version];
}
function wp_enqueue_script($handle,$src,$deps=[],$version=null,$footer=true): void {
    $GLOBALS['scripts'][$handle] = ['deps'=>$deps,'footer'=>$footer,'version'=>$version];
}
function wp_script_add_data($handle,$key,$value): void {
    $GLOBALS['script_data'][$handle][$key] = $value;
}
function wp_localize_script($handle,$name,$value): void {
    $GLOBALS['localizations'][$handle][$name] = $value;
}
function wp_script_is($handle,$status): bool {
    return $handle === 'wc-checkout' && $GLOBALS['wc_checkout_registered'];
}
function rest_url($path): string { return 'https://theme.local.test/wp-json/'.$path; }
function wp_create_nonce($name): string { return $name === 'wc_store_api' ? 'fixed-fixture' : 'unexpected'; }
function is_front_page(): bool { return $GLOBALS['route'] === 'homepage'; }
function is_shop(): bool { return $GLOBALS['route'] === 'catalog'; }
function is_product_taxonomy(): bool { return false; }
function is_product(): bool { return $GLOBALS['route'] === 'product'; }
function is_page($slug): bool {
    return ($GLOBALS['route'] === 'shipping' && ($slug === 'free-shipping' || $slug === 'free-shipping-guide'))
        || ($GLOBALS['route'] === 'support' && $slug === 'track-your-order');
}
function is_cart(): bool { return $GLOBALS['route'] === 'cart'; }
function is_checkout(): bool { return in_array($GLOBALS['route'], ['checkout', 'orderpay', 'received'], true); }
function is_checkout_pay_page(): bool { return $GLOBALS['route'] === 'orderpay'; }
function is_order_received_page(): bool { return $GLOBALS['route'] === 'received'; }
function check(bool $yes,string $why): void {
    if (!$yes) { fwrite(STDERR, "FAIL: ".$why."\n"); exit(1); }
}
require dirname(__DIR__).'/theme/beyourlover-v3/inc/theme-os-assets.php';
check(count($GLOBALS['actions']['wp_enqueue_scripts'] ?? []) === 1, 'one enqueue callback');
check(($GLOBALS['actions']['wp_enqueue_scripts'][0]['priority'] ?? null) === 10, 'original priority 10');
$callback = $GLOBALS['actions']['wp_enqueue_scripts'][0]['callback'];
$stylesAlways = ['byl-v3-foundation','byl-v3-tokens','byl-v3-components','byl-v3-component-library','byl-v3-shell','byl-v3-motion'];
$routes = [
    'generic'  => [[],[]],
    'homepage' => [['byl-v3-homepage'],[]],
    'catalog'  => [['byl-v3-archive'],[]],
    'product'  => [['byl-v3-product'],['byl-v3-product']],
    'shipping' => [['byl-v3-free-shipping'],['byl-v3-free-shipping-v2']],
    'support'  => [['byl-v3-track-order'],[]],
    'cart'     => [['byl-v3-cart','byl-v3-cart-focus'],['byl-v3-cart-experience','byl-v3-cart']],
    'checkout' => [['byl-v3-checkout','byl-v3-checkout-shell'],['byl-v3-checkout','byl-v3-checkout-newsletter']],
    'orderpay' => [['byl-v3-checkout','byl-v3-checkout-shell','byl-v3-order-pay'],['byl-v3-checkout','byl-v3-checkout-newsletter']],
    'received' => [[],[]],
];
foreach ($routes as $route=>$pair) {
    $GLOBALS['route'] = $route;
    $GLOBALS['styles'] = $GLOBALS['scripts'] = $GLOBALS['localizations'] = $GLOBALS['script_data'] = [];
    $callback();
    check(array_keys($GLOBALS['styles']) === array_merge($stylesAlways,$pair[0]), "$route style scope/order");
    check(array_keys($GLOBALS['scripts']) === array_merge(['byl-v3-shell'],$pair[1]), "$route script scope/order");
    check(($GLOBALS['script_data']['byl-v3-shell']['strategy'] ?? null) === 'defer', "$route shell defer");
    check(($GLOBALS['styles']['byl-v3-tokens']['deps'] ?? []) === ['byl-v3-foundation'], "$route tokens chain");
    if ($route === 'cart') {
        check($GLOBALS['scripts']['byl-v3-cart']['deps'] === ['wp-data','wc-blocks-data-store'], 'native Cart dependencies');
        $payload = $GLOBALS['localizations']['byl-v3-cart']['BYLShippingPromotionCart'] ?? [];
        check($payload['nonce'] === 'fixed-fixture', 'Cart native nonce');
        check(str_contains($payload['applyUrl'],'wc/store/v1/cart/apply-coupon'), 'Cart native Store API apply route');
        check(str_contains($payload['removeUrl'],'wc/store/v1/cart/remove-coupon'), 'Cart native Store API remove route');
        check(($GLOBALS['script_data']['byl-v3-cart']['strategy'] ?? null) === 'defer', 'Cart defer');
    } else {
        check(!isset($GLOBALS['localizations']['byl-v3-cart']), "$route no Cart localization");
    }
    if ($route === 'checkout' || $route === 'orderpay') {
        check($GLOBALS['scripts']['byl-v3-checkout']['deps'] === ['jquery','wc-checkout'], 'native Checkout dependency');
        check($GLOBALS['scripts']['byl-v3-checkout-newsletter']['deps'] === ['byl-v3-checkout'], 'Newsletter script depends exclusively on native Checkout');
        $payload = $GLOBALS['localizations']['byl-v3-checkout']['BYLShippingPromotionCheckout'] ?? [];
        check($payload['nonce'] === 'fixed-fixture' && str_contains($payload['cartUrl'],'wc/store/v1/cart'), 'Checkout native Store API');
        check(($GLOBALS['script_data']['byl-v3-checkout']['strategy'] ?? null) === 'defer', 'Checkout defer');
    } else {
        check(!isset($GLOBALS['localizations']['byl-v3-checkout']), "$route no Checkout localization");
    }
}
$GLOBALS['route'] = 'checkout';
$GLOBALS['wc_checkout_registered'] = false;
$GLOBALS['scripts'] = [];
$callback();
check($GLOBALS['scripts']['byl-v3-checkout']['deps'] === ['jquery'], 'Checkout dependency fallback when wc-checkout not registered');
echo "PASS Theme OS T2.4 — scoped asset handles, CSS/JS order, Woo dependencies, localize/nonces, no route leakage\n";
