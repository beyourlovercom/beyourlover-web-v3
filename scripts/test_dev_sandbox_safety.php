<?php
/** Offline/side-effect-free negative-path contract tests for BYL Dev Safety MU. */
declare(strict_types=1);
error_reporting(E_ALL);
define('ABSPATH', __DIR__);
$GLOBALS['byl_test_hooks'] = [];
$GLOBALS['byl_test_notices'] = [];
function wp_get_environment_type(): string { return 'staging'; }
function home_url(): string { return 'https://dev.beyourlover.com'; }
function is_ssl(): bool { return true; }
function add_filter($hook, $callback, $priority = 10, $accepted_args = 1): void {
    $GLOBALS['byl_test_hooks'][$hook][] = $callback;
}
function add_action($hook, $callback, $priority = 10, $accepted_args = 1): void {
    add_filter($hook, $callback, $priority, $accepted_args);
}
function wc_add_notice(string $message, string $type): void {
    $GLOBALS['byl_test_notices'][] = compact('message', 'type');
}
class WP_REST_Request {
    public function __construct(private string $method, private string $route) {}
    public function get_route(): string { return $this->route; }
    public function get_method(): string { return $this->method; }
}
class WP_Error {
    public function __construct(public string $code, public string $message, public array $data) {}
}
function assert_equal($actual, $expected, string $message): void {
    if ($actual !== $expected) { throw new RuntimeException($message . ': expected ' . var_export($expected, true) . ', got ' . var_export($actual, true)); }
}
function run_hook(string $name, ...$args) {
    $result = null;
    foreach ($GLOBALS['byl_test_hooks'][$name] ?? [] as $callback) {
        $result = $callback(...$args);
    }
    return $result;
}
require dirname(__DIR__) . '/dev/mu-plugins/byl-dev-safety.php';
assert_equal(byl_dev_p13_checkout_gate(), [], 'absent private gate must fail closed');
$gateways = ['woocommerce_payments' => (object)['id'=>'woocommerce_payments'], 'stripe'=>(object)['id'=>'stripe']];
$gated = ($GLOBALS['byl_test_hooks']['woocommerce_available_payment_gateways'][0])($gateways);
assert_equal($gated, [], 'anonymous visitors must not see payment methods');
foreach (['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
    foreach (['/wc/store/v1/checkout', '/wc/store/checkout'] as $route) {
        $error = run_hook('rest_pre_dispatch', null, null, new WP_REST_Request($method, $route));
        if (!($error instanceof WP_Error)) { throw new RuntimeException("$method $route must fail closed"); }
        assert_equal($error->code, 'byl_dev_sandbox_checkout_closed', "error code $method $route");
        assert_equal($error->data['status'], 403, "forbidden status $method $route");
    }
}
assert_equal(run_hook('rest_pre_dispatch', null, null, new WP_REST_Request('OPTIONS', '/wc/store/v1/checkout')), null, 'OPTIONS remains available');
foreach (['/wc/store/v1/cart', '/wc/store/v1/cart/apply-coupon', '/woo/address-autocomplete/search', '/wp/v2/posts'] as $route) {
    assert_equal(run_hook('rest_pre_dispatch', null, null, new WP_REST_Request('POST', $route)), null, "unrelated $route stays untouched");
}
$prior = new WP_Error('prior_denial', 'prior', ['status' => 401]);
assert_equal(run_hook('rest_pre_dispatch', $prior, null, new WP_REST_Request('POST', '/wc/store/v1/checkout')), $prior, 'preserve earlier REST denials');
assert_equal(run_hook('pre_wp_mail'), false, 'outbound mail blocked');
($GLOBALS['byl_test_hooks']['woocommerce_checkout_process'][0])();
assert_equal(count($GLOBALS['byl_test_notices']), 1, 'classic checkout must emit one denial notice');
assert_equal($GLOBALS['byl_test_notices'][0]['type'], 'error', 'classic error notice required');
$robots = ($GLOBALS['byl_test_hooks']['wp_robots'][0])([]);
foreach (['noindex','nofollow','noarchive'] as $key) assert_equal($robots[$key], true, "robots $key required");
echo "DEV_SANDBOX_SAFETY_NEGATIVE_TESTS=PASS (Store API + Classic + Mail + Privacy)\n";
