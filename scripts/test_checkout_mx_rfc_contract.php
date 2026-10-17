<?php
/**
 * Standalone RFC domain and native Woo checkout-hook tests.
 * Isolated stubs; NO WordPress boot, DB, customers, real orders, or mail.
 */
define('ABSPATH', '/test/');
$GLOBALS['test_hooks'] = [];
function add_filter($name, $fn, $priority = 10) { $GLOBALS['test_hooks'][$name][] = $fn; }
function add_action($name, $fn, $priority = 10, $args = 1) { $GLOBALS['test_hooks'][$name][] = $fn; }
function sanitize_text_field($value) { return trim(strip_tags((string)$value)); }
function __($text, $domain) { return $text; }
class WC_Order {
    public $meta = [];
    public function update_meta_data($key, $value) { $this->meta[$key] = $value; }
}
class Test_WP_Errors {
    public $errors = [];
    public function add($key, $message) { $this->errors[$key] = $message; }
}
require __DIR__ . '/../theme/beyourlover-v3/inc/checkout-mx-rfc.php';
$checks = 0;
function check_test($pass, $message) {
    global $checks;
    if (!$pass) { throw new RuntimeException('FAIL: ' . $message); }
    $checks++;
}
check_test(isset($GLOBALS['test_hooks']['woocommerce_checkout_fields'][0]), 'native Woo field hook');
check_test(isset($GLOBALS['test_hooks']['woocommerce_after_checkout_validation'][0]), 'server validation hook');
check_test(isset($GLOBALS['test_hooks']['woocommerce_checkout_create_order'][0]), 'HPOS CRUD hook');
$f = ($GLOBALS['test_hooks']['woocommerce_checkout_fields'][0])(['billing' => []]);
check_test(isset($f['billing']['billing_rfc_tax_id']), 'genuine registered Woo field');
check_test($f['billing']['billing_rfc_tax_id']['required'] === false, 'must not block other countries globally');
check_test($f['billing']['billing_rfc_tax_id']['priority'] === 95, 'postal then RFC then organization');
check_test(($f['billing']['billing_rfc_tax_id']['custom_attributes']['maxlength'] ?? null) === '13', 'max input');
check_test(byl_v3_checkout_normalize_mx_rfc(' abc010101ab1 ') === 'ABC010101AB1', 'uppercase/trim');
check_test(byl_v3_checkout_valid_mx_rfc('ABC010101AB1'), '12-char company');
check_test(byl_v3_checkout_valid_mx_rfc('GODE561231GR8'), '13-char individual');
check_test(!byl_v3_checkout_valid_mx_rfc('SAVM8900101ABC'), 'reject 14-character screenshot example');
check_test(!byl_v3_checkout_valid_mx_rfc('X'), 'reject malformed');
$validate = $GLOBALS['test_hooks']['woocommerce_after_checkout_validation'][0];
$errors = new Test_WP_Errors();
$validate(['billing_country' => 'MX', 'billing_rfc_tax_id' => ''], $errors);
check_test(isset($errors->errors['billing_rfc_tax_id_invalid']), 'MX required even if JS bypassed');
$errors = new Test_WP_Errors();
$validate(['billing_country' => 'MX', 'billing_rfc_tax_id' => 'WRONG'], $errors);
check_test(count($errors->errors) === 1, 'MX malformed blocked');
$errors = new Test_WP_Errors();
$validate(['billing_country' => 'MX', 'billing_rfc_tax_id' => 'GODE561231GR8'], $errors);
check_test($errors->errors === [], 'MX valid accepted');
$errors = new Test_WP_Errors();
$validate(['billing_country' => 'US', 'billing_rfc_tax_id' => ''], $errors);
check_test($errors->errors === [], 'non-MX no RFC requirement');
$order = new WC_Order();
$save = $GLOBALS['test_hooks']['woocommerce_checkout_create_order'][0];
$save($order, ['billing_country' => 'US', 'billing_rfc_tax_id' => 'GODE561231GR8']);
check_test($order->meta === [], 'never save RFC for other countries');
$save($order, ['billing_country' => 'MX', 'billing_rfc_tax_id' => 'INVALID']);
check_test($order->meta === [], 'never save malformed RFC');
$save($order, ['billing_country' => 'MX', 'billing_rfc_tax_id' => 'GODE561231GR8']);
check_test(($order->meta['_billing_rfc_tax_id'] ?? '') === 'GODE561231GR8', 'HPOS-safe private order metadata');
check_test(count($order->meta) === 1, 'no unrelated order fields');
echo "PASS Mexico RFC native Woo field: {$checks} isolated assertions, zero real business writes\n";
