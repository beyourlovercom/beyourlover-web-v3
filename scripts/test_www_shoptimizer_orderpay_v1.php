<?php
/** Isolated Order Pay V1 authorization + display + asset contract. No WP runtime. */
declare(strict_types=1);
define('ABSPATH', __DIR__);
$GLOBALS['hooks'] = [];
$GLOBALS['pay_route'] = true;
$GLOBALS['capable'] = true;
$GLOBALS['query_id'] = 100;
$GLOBALS['styles'] = [];
function add_action(string $name, $fn, int $priority = 10, int $accepted = 1): void {
    $GLOBALS['hooks'][$name][] = ['fn' => $fn, 'priority' => $priority, 'accepted' => $accepted];
}
function is_checkout_pay_page(): bool { return $GLOBALS['pay_route']; }
function absint($v): int { return abs((int)$v); }
function get_query_var($name): int { return $name === 'order-pay' ? $GLOBALS['query_id'] : 0; }
function wc_clean($s): string { return strip_tags((string)$s); }
function wp_unslash($s): string { return stripslashes($s); }
function current_user_can($role, $id): bool { return $role==='pay_for_order' && $id === 100 && $GLOBALS['capable']; }
function esc_html_e($s, $domain): void { echo htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function esc_html($s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function wp_kses_post($s): string { return strip_tags($s, '<br><strong><em>'); }
function esc_url($s): string { return $s; }
function home_url($s): string { return 'https://www.beyourlover.com' . $s; }
function get_stylesheet_directory(): string { return $GLOBALS['fixture_theme_dir']; }
function get_stylesheet_directory_uri(): string { return 'https://www.beyourlover.com/wp-content/themes/shoptimizer-child-theme'; }
function wp_enqueue_style($h, $url, $deps, $v): void { $GLOBALS['styles'][$h] = [$url,$deps,$v]; }
class WC_Order {
    public $payable = true;
    public $shipping = 'Test Name<br>1 Fictional Way';
    public $billing = 'Test Name<br>2 Fictional Way';
    public function get_id(): int { return 100; }
    public function get_order_key(): string { return 'wc_order_test-100'; }
    public function needs_payment(): bool { return $this->payable; }
    public function get_formatted_shipping_address(): string { return $this->shipping; }
    public function get_formatted_billing_address(): string { return $this->billing; }
    public function get_shipping_phone(): string { return '000-111'; }
    public function get_billing_phone(): string { return '000-222'; }
    public function get_billing_email(): string { return 'synthetic@example.invalid'; }
}
function check($condition, string $message): void { if (!$condition) { fwrite(STDERR, "FAIL ".$message."\n"); exit(1); } }
$GLOBALS['fixture_theme_dir'] = sys_get_temp_dir().'/byl-orderpay-v1-test-'.getmypid();
mkdir($GLOBALS['fixture_theme_dir'].'/assets/css', 0700, true);
file_put_contents($GLOBALS['fixture_theme_dir'].'/assets/css/byl-www-orderpay-v1.css', '/* fixture */');
require dirname(__DIR__).'/release/byl-www-orderpay-v1.php';
check(isset($GLOBALS['hooks']['before_woocommerce_pay_form'][0]), 'canonical validated order-pay hook');
check(!isset($GLOBALS['hooks']['woocommerce_checkout_process']), 'no checkout write interception');
check(isset($GLOBALS['hooks']['wp_enqueue_scripts'][0]), 'scoped asset hook');
$render = $GLOBALS['hooks']['before_woocommerce_pay_form'][0]['fn'];
$assets = $GLOBALS['hooks']['wp_enqueue_scripts'][0]['fn'];
$order = new WC_Order();
$_GET['key'] = $order->get_order_key();
function display($fn, $order): string { ob_start(); $fn($order); return ob_get_clean(); }

check(display($render, $order) !== '', 'valid authorized order displays panel');
$valid = display($render, $order);
foreach (['Shipping address', 'Billing address', 'synthetic@example.invalid', '000-111','000-222','data-byl-www-orderpay-address'] as $fragment) {
    check(str_contains($valid, $fragment), 'missing expected rendered field: '.$fragment);
}
check(str_contains($valid, '/contact-us/'), 'verified support URL');

$cases=0;
$negative = function($reason) use (&$cases,$render,$order): void {
    check(display($render, $order) === '', 'PII leak: '.$reason);
    $cases++;
};
$GLOBALS['pay_route'] = false; $negative('not order-pay route'); $GLOBALS['pay_route'] = true;
$GLOBALS['query_id'] = 999; $negative('route order mismatch'); $GLOBALS['query_id'] = 100;
$GLOBALS['capable'] = false; $negative('not permitted'); $GLOBALS['capable'] = true;
$_GET['key'] = 'incorrect'; $negative('incorrect order key');
unset($_GET['key']); $negative('missing order key');
$_GET['key'] = [$order->get_order_key()]; $negative('array order key');
$_GET['key'] = $order->get_order_key();
$order->payable = false; $negative('not payable'); $order->payable = true;
check(display($render, new stdClass()) === '', 'non-order denied');

$order->shipping = ''; $order->billing = '';
$noAddress = display($render, $order);
check(str_contains($noAddress, 'No shipping address is saved'), 'missing shipping fallback');
check(str_contains($noAddress, 'No billing address is saved'), 'missing billing fallback');

$GLOBALS['styles'] = [];
$GLOBALS['pay_route'] = false; $assets();
check($GLOBALS['styles'] === [], 'ordinary checkout must not load legacy order-pay stylesheet');
$GLOBALS['pay_route'] = true; $assets();
check(isset($GLOBALS['styles']['byl-www-orderpay-v1']), 'Order Pay CSS enqueued');
check(str_contains($GLOBALS['styles']['byl-www-orderpay-v1'][0], '/assets/css/byl-www-orderpay-v1.css'), 'CSS location');
unlink($GLOBALS['fixture_theme_dir'].'/assets/css/byl-www-orderpay-v1.css');
rmdir($GLOBALS['fixture_theme_dir'].'/assets/css');
rmdir($GLOBALS['fixture_theme_dir'].'/assets');
rmdir($GLOBALS['fixture_theme_dir']);
echo "WWW_SHOPTIMIZER_ORDERPAY_V1_OFFLINE=PASS (authorized rendering, negative authorization, fallback, scoped CSS)\n";
