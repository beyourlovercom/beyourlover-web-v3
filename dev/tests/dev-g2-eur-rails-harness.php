<?php
declare(strict_types=1);
/** Offline only: exercise the Dev G2 EUR method allowlist and fail-closed cart rules. */
define('ABSPATH', __DIR__);
function wp_get_environment_type() { return 'staging'; }
function add_filter() { return true; }
function add_action() { return true; }
$GLOBALS['test_currency'] = 'EUR';
function get_woocommerce_currency() { return $GLOBALS['test_currency']; }
class BylCart {
    public $country = 'BE'; public $total = '113.38'; public $product = 36089;
    public $qty = 1; public $coupons = []; public $lines = 1;
    function get_cart(): array { return $this->lines ? [ [ 'product_id' => $this->product, 'quantity' => $this->qty ] ] : []; }
    function get_cart_contents_count(): int { return $this->qty; }
    function get_applied_coupons(): array { return $this->coupons; }
    function get_total($context) { return $this->total; }
}
class BylCustomer {
    public $billing = 'BE'; public $shipping = 'BE';
    function get_billing_country(){ return $this->billing; }
    function get_shipping_country(){ return $this->shipping; }
}
class BylWC {
    public $cart; public $customer;
    function __construct(){ $this->cart = new BylCart(); $this->customer = new BylCustomer(); }
}
$GLOBALS['wc'] = new BylWC();
function WC() { return $GLOBALS['wc']; }
require dirname(__DIR__) . '/mu-plugins/byl-dev-safety.php';
function check(bool $condition, string $name): void { if (!$condition) { fwrite(STDERR, "FAIL $name\n"); exit(1); } echo "PASS $name\n"; }
check(byl_dev_p13_gateway_country('woocommerce_payments') === '', 'legacy card remains');
check(byl_dev_p13_gateway_country('woocommerce_payments_bancontact') === 'BE', 'BE only');
check(byl_dev_p13_gateway_country('woocommerce_payments_eps') === 'AT', 'AT only');
check(byl_dev_p13_gateway_country('woocommerce_payments_ideal') === 'NL', 'NL only');
check(byl_dev_p13_gateway_country('stripe') === null, 'unsupported rail rejected');
check(byl_dev_p13_gateway_country('woocommerce_payments_ideal_fake') === null, 'suffix rejected');
check(byl_dev_p13_eur_canary_matches('woocommerce_payments_bancontact'), 'EUR BE 11338 match');
$GLOBALS['wc']->customer->billing = 'NL';
check(!byl_dev_p13_eur_canary_matches('woocommerce_payments_bancontact'), 'wrong billing country rejected');
$GLOBALS['wc']->customer->billing = 'BE'; $GLOBALS['wc']->customer->shipping = 'AT';
check(!byl_dev_p13_eur_canary_matches('woocommerce_payments_bancontact'), 'wrong shipping country rejected');
$GLOBALS['wc']->customer->shipping = 'BE'; $GLOBALS['test_currency'] = 'USD';
check(!byl_dev_p13_eur_canary_matches('woocommerce_payments_bancontact'), 'USD currency rejected');
$GLOBALS['test_currency'] = 'EUR'; $GLOBALS['wc']->cart->total = '126.40';
check(!byl_dev_p13_eur_canary_matches('woocommerce_payments_bancontact'), 'incorrect USD numeric amount rejected');
$GLOBALS['wc']->cart->total = '113.38'; $GLOBALS['wc']->cart->qty = 2;
check(!byl_dev_p13_eur_canary_matches('woocommerce_payments_bancontact'), 'quantity >1 rejected');
$GLOBALS['wc']->cart->qty = 1; $GLOBALS['wc']->cart->coupons = ['X'];
check(!byl_dev_p13_eur_canary_matches('woocommerce_payments_bancontact'), 'coupons rejected');
$GLOBALS['wc']->cart->coupons = []; $GLOBALS['wc']->cart->product = 999;
check(!byl_dev_p13_eur_canary_matches('woocommerce_payments_bancontact'), 'other product rejected');
$GLOBALS['wc']->cart->product = 36089; $GLOBALS['wc']->cart->lines = 0;
check(!byl_dev_p13_eur_canary_matches('woocommerce_payments_bancontact'), 'empty cart rejected');
$GLOBALS['wc']->cart->lines = 1; $GLOBALS['wc']->customer->billing = 'AT'; $GLOBALS['wc']->customer->shipping = 'AT';
check(byl_dev_p13_eur_canary_matches('woocommerce_payments_eps'), 'AT/EPS match');
$GLOBALS['wc']->customer->billing = 'NL'; $GLOBALS['wc']->customer->shipping = 'NL';
check(byl_dev_p13_eur_canary_matches('woocommerce_payments_ideal'), 'NL/iDEAL match');
check(!byl_dev_p13_eur_canary_matches('woocommerce_payments'), 'legacy card not restricted as EUR local rail');
echo "G2_EUR_NEGATIVE_TESTS_PASS\n";
