<?php
/**
 * P5 Store API fail-closed regression for the *existing* BYL Dev Safety MU.
 * Run: php tools/dev/tests/p5-store-api-checkout-deny-smoke.php
 * Mock-only; makes no HTTP request, payment, order, cookie or DB write.
 */
declare(strict_types=1);

define('ABSPATH', '/isolated-p5/');
$GLOBALS['p5_hooks'] = [];
$GLOBALS['p5_env'] = 'staging';
$GLOBALS['p5_site'] = 'https://dev.beyourlover.com';

function add_action($name, $callback, $priority = 10, $accepted_args = 1): void {}
function add_filter($name, $callback, $priority = 10, $accepted_args = 1): void {
    $GLOBALS['p5_hooks'][$name][] = ['callback' => $callback, 'priority' => $priority, 'args' => $accepted_args];
}
function wp_get_environment_type(): string { return $GLOBALS['p5_env']; }
function home_url($path = ''): string { return $GLOBALS['p5_site'].$path; }

class WP_REST_Request {
    public function __construct(private string $method, private string $route) {}
    public function get_route(): string { return $this->route; }
    public function get_method(): string { return $this->method; }
}
class WP_Error {
    public function __construct(private string $code, private string $message, private array $data) {}
    public function get_error_code(): string { return $this->code; }
    public function get_error_data(): array { return $this->data; }
}

require dirname(__DIR__, 3).'/dev/mu-plugins/byl-dev-safety.php';
$registered = array_values(array_filter(
    $GLOBALS['p5_hooks']['rest_pre_dispatch'] ?? [],
    static fn (array $entry) => $entry['priority'] === 99 && $entry['args'] === 3
));
if (count($registered) !== 1) { throw new RuntimeException('Store API guard must register once at priority 99 / 3 args'); }
$filter = $registered[0]['callback'];
$count = 0;
function verify_p5(bool $ok, string $message): void {
    global $count;
    if (!$ok) { throw new RuntimeException('FAIL: '.$message); }
    $count++;
}

// Both Store API namespace aliases, checkout base and numeric order-ID routes.
$deny = ['/wc/store/v1/checkout', '/wc/store/checkout',
    '/wc/store/v1/checkout/39923', '/wc/store/checkout/39923',
    '/wc/store/v1/checkout/', '/wc/store/v1/checkout/39923/'];
foreach ($deny as $route) {
    foreach (['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
        $result = $filter(null, null, new WP_REST_Request($method, $route));
        verify_p5($result instanceof WP_Error
            && $result->get_error_code() === 'byl_dev_sandbox_checkout_closed'
            && ($result->get_error_data()['status'] ?? null) === 403,
            $method.' '.$route.' must be hard-closed');
    }
    verify_p5($filter(null, null, new WP_REST_Request('OPTIONS', $route)) === null,
        'OPTIONS must remain available for '.$route);
}

// Cart/product REST APIs, malformed IDs, and unrelated paths remain untouched.
foreach (['/wc/store/v1/cart', '/wc/store/v1/cart/items',
    '/wc/store/v1/products', '/wc/store/v1/checkout/not-an-id',
    '/wc/store/v1/checkout/39923/pay'] as $route) {
    verify_p5($filter(null, null, new WP_REST_Request('POST', $route)) === null,
        'Unrelated route unaffected '.$route);
}
$prior = new WP_Error('another_filter', 'Already handled', ['status' => 418]);
verify_p5($filter($prior, null, new WP_REST_Request('POST', $deny[0])) === $prior,
    'Never replace a prior REST dispatch result');
verify_p5($filter(null, null, (object) ['route' => $deny[0]]) === null,
    'Ignore non-REST-request objects');

// Site/environment guard prevents affecting any non-Dev WordPress runtime.
$GLOBALS['p5_site'] = 'https://www.beyourlover.com';
verify_p5($filter(null, null, new WP_REST_Request('POST', $deny[0])) === null,
    'Production host must not be modified');
$GLOBALS['p5_site'] = 'https://dev.beyourlover.com';
$GLOBALS['p5_env'] = 'production';
verify_p5($filter(null, null, new WP_REST_Request('POST', $deny[0])) === null,
    'Production environment must not be modified');

echo 'P5_STORE_API_FAIL_CLOSED=PASS '.$count.' checks (mock only)'.PHP_EOL;
