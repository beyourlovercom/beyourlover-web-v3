<?php
/**
 * Developer-only, WooCommerce-native source compatibility probe.
 * Invocation: wp --path=<DEV_WORDPRESS_ROOT> --skip-themes eval-file <this file>
 * Source file: ../release-candidates/www-shipping-production-candidate-v1.php
 *
 * Read-only: no shipping filters installed, no Woo settings changes, no cart
 * session writes, no order creation, no payments. NEVER RUN ON WWW.
 */
declare(strict_types=1);
if (!defined('WP_CLI') || WP_CLI !== true
    || !function_exists('home_url')
    || rtrim(home_url(), '/') !== 'https://dev.beyourlover.com') {
    fwrite(STDERR, "DENIED: this probe only runs on Dev using WP-CLI\n");
    exit(3);
}
if (!class_exists('WooCommerce')
    || !class_exists('WC_Shipping_Rate')
    || !class_exists('WC_Tax')
    || !class_exists('WC_Shipping')) {
    fwrite(STDERR, "FAIL: native WooCommerce classes missing\n");
    exit(3);
}
$source = dirname(__DIR__) . '/release-candidates/www-shipping-production-candidate-v1.php';
if (!is_file($source)) {
    fwrite(STDERR, "FAIL: exact source candidate missing\n");
    exit(3);
}
$assertions = 0;
$check = static function (bool $condition, string $name) use (&$assertions): void {
    $assertions++;
    if (!$condition) {
        fwrite(STDERR, "FAIL native Woo shipping: $name\n");
        exit(2);
    }
};
$check(!function_exists('byl_www_ship_rates'),
    'candidate does not already run on Dev');
$beforeFilter = has_filter('woocommerce_package_rates');
$beforePackages = has_filter('woocommerce_cart_shipping_packages');
require $source;
$check(function_exists('byl_www_ship_rates'),
    'candidate functions load under native Woo without bootstrap');
$check(!byl_www_ship_armed() && !byl_www_ship_gate(),
    'WWW candidate cannot be armed on Dev');
$check($beforeFilter === has_filter('woocommerce_package_rates'),
    'no shipping rate hook was added by loading candidate');
$check($beforePackages === has_filter('woocommerce_cart_shipping_packages'),
    'no shipping package hook was added by loading candidate');

$sample = [
    'destination' => ['country'=>'US','state'=>'CA','postcode'=>'90001'],
    'contents'=>[
        'regular'=>['product_id'=>37763,'line_total'=>20.0,'quantity'=>1],
        'special'=>['product_id'=>9153,'line_total'=>179.0,'quantity'=>1],
        'pillow'=>['product_id'=>38047,'line_total'=>59.0,'quantity'=>1],
    ],
    'contents_cost'=>258.0
];
$check(byl_www_ship_packages([$sample]) === [$sample],
    'disabled production candidate leaves native packages untouched');
$check(byl_www_ship_product_kind($sample['contents']['regular'])==='ordinary',
    'ordinary product identity');
$check(byl_www_ship_product_kind($sample['contents']['special'])==='special',
    'special product identity');
$check(byl_www_ship_product_kind($sample['contents']['pillow'])==='pillow',
    'pillow product identity');
$check(byl_www_ship_subtotal($sample['contents'])===258.0,
    'Woo after-coupon line_total subtotal');

$rate = new WC_Shipping_Rate(
    'flat_rate:10', 'Standard', 10.0, [], 'flat_rate', 10, 'taxable'
);
$check($rate->get_id()==='flat_rate:10'
    && $rate->get_method_id()==='flat_rate'
    && $rate->get_instance_id()===10
    && (float)$rate->get_cost()===10.0,
    'real WC_Shipping_Rate method and instance');
$check($rate->get_tax_status()==='taxable',
    'real Woo rate tax status API');
$clone=clone $rate;
$clone->set_cost(30.0);
$clone->set_label('Extra $30 Shipping');
$check((float)$clone->get_cost()===30.0
    && $clone->get_label()==='Extra $30 Shipping'
    && (float)$rate->get_cost()===10.0,
    'real Woo rate cloning and independent cost modification');
$clone->set_taxes(['fixture_tax'=>2.13]);
$check(is_array($clone->get_taxes())
    && (float)($clone->get_taxes()['fixture_tax']??0)===2.13,
    'real Woo tax setter/getter accepts recalculated taxes');
$check(byl_www_ship_rates([$rate->get_id()=>$rate],$sample)
    === [$rate->get_id()=>$rate],
    'rates filter is inert without production Owner gate');

if (!method_exists(WC()->shipping(),'get_package_hash')) {
    fwrite(STDERR, "FAIL: Woo shipping package hash method absent\n");
    exit(2);
}
$hashMethod=new ReflectionMethod(WC()->shipping(), 'get_package_hash');
$before=$hashMethod->invoke(WC()->shipping(), $sample);
$armed=$sample;
$armed['byl_www_ship_runtime_marker']=str_repeat('a',64);
$hashA=$hashMethod->invoke(WC()->shipping(), $armed);
$armed['byl_www_ship_runtime_marker']=str_repeat('b',64);
$hashB=$hashMethod->invoke(WC()->shipping(), $armed);
$check(is_string($before)&&$before!==$hashA&&$hashA!==$hashB,
    'actual Woo package hash tracks source-controlled runtime marker');

$testTaxes=WC_Tax::calc_shipping_tax(30.0, []);
$check(is_array($testTaxes),
    'real Woo tax calculator returns array without customer/checkout');
echo "PASS_DEV_WOO_NATIVE_SHIPPING_READONLY assertions={$assertions}"
   . " wc_version=" . (defined('WC_VERSION')?WC_VERSION:'unknown')
   . " checkout_orders=0 settings_writes=0 hooks_added=0\n";
