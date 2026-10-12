<?php
/**
 * G5 Dev-only AfterShip / Automizely outbound host boundary regression.
 * Run: php tools/dev/tests/g5-aftership-dev-outbound-isolation-smoke.php
 * Pure WP mocks; no Internet, real WordPress, Woo order, mail, provider calls or file writes.
 */
declare(strict_types=1);

define('ABSPATH', '/g5-isolated/');
$GLOBALS['g5_hooks'] = [];
$GLOBALS['g5_env'] = ['site' => 'https://dev.beyourlover.com', 'environment' => 'staging'];
function add_filter($name, $callback, $priority = 10, $args = 1): void {
    $GLOBALS['g5_hooks'][$name][$priority][] = ['callback' => $callback, 'args' => $args];
}
function add_action($name, $callback, $priority = 10, $args = 1): void { add_filter($name, $callback, $priority, $args); }
function wp_get_environment_type(): string { return $GLOBALS['g5_env']['environment']; }
function home_url($path = ''): string { return $GLOBALS['g5_env']['site'] . $path; }
function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
class WP_Error {
    public function __construct(private string $code, private string $message = '', private array $data = []) {}
    public function get_error_code(): string { return $this->code; }
    public function get_error_message(): string { return $this->message; }
    public function get_error_data(): array { return $this->data; }
}

class WP_REST_Request {
    public function __construct(private string $route, private string $method = 'GET') {}
    public function get_route(): string { return $this->route; }
    public function get_method(): string { return $this->method; }
}
require dirname(__DIR__, 3) . '/dev/mu-plugins/byl-dev-safety.php';
$hooks = $GLOBALS['g5_hooks']['pre_http_request'] ?? [];
if (count($hooks[PHP_INT_MAX] ?? []) !== 1
    || ($hooks[PHP_INT_MAX][0]['args'] ?? 0) !== 3) {
    throw new RuntimeException('G5 network-blocking hook must have exactly one highest-priority handler with three args');
}
$guard = $hooks[PHP_INT_MAX][0]['callback'];
$checks = 0;
function expect(bool $condition, string $name): void {
    global $checks;
    if (!$condition) { throw new RuntimeException('FAIL ' . $name); }
    $checks++;
    echo 'PASS ' . $name . PHP_EOL;
}
$blocked = [
    'https://api.automizely.com/aftership/public/graphql',
    'https://webhooks.automizely.com/example',
    'https://AUTOMIZELY.COM/',
    'https://foo.bar.automizely.com/track',
    'http://api.aftership.com/v4/trackings',
    'https://aftership.com/',
    'https://sub.aftership.com:443/v4/trackings',
    'https://api.aftership.com./v4/trackings',
    'https://user:fake@api.automizely.com/graphql',
];
foreach ($blocked as $i => $url) {
    $r = $guard(false, ['method'=>'POST'], $url);
    expect($r instanceof WP_Error
        && $r->get_error_code() === 'byl_dev_aftership_outbound_closed',
        'dev blocks true provider hostname ' . ($i + 1));
}
$allowed = [
    'https://api.stripe.com/v1/payment_intents',
    'https://public-api.wordpress.com/',
    'https://connect.woocommerce.com/',
    'https://wordpress.org/',
    'https://api.aftership.com.attacker.example/v4',
    'https://notaftership.com/',
    'https://fooautomizely.com/',
    'https://automizely.com.evil.example/',
    'https://test.automizely.com.evil.example/',
    'https://api.shippo.com/',
    'https://sandbox-api.woocommerce.com/',
    'https://example.com/path/aftership.com',
    'invalid-url-without-host',
];
foreach ($allowed as $i => $url) {
    expect($guard(false, ['method'=>'POST'], $url) === false,
        'Dev preserves non-provider destinations ' . ($i + 1));
}
$already = new WP_Error('earlier_filter_denial', 'Preserve an earlier WordPress HTTP preemption');
expect($guard($already, [], 'https://api.automizely.com') === $already,
    'prior HTTP interception result stays unchanged');
expect($guard(['response' => ['code' => 200]], [], 'https://api.automizely.com') === ['response' => ['code' => 200]],
    'prior nonfalse interception result stays unchanged');
$GLOBALS['g5_env']['environment'] = 'production';
expect($guard(false, [], 'https://api.automizely.com') === false, 'production environment is unaffected');
$GLOBALS['g5_env']['environment'] = 'staging';
$GLOBALS['g5_env']['site'] = 'https://www.beyourlover.com';
expect($guard(false, [], 'https://api.automizely.com') === false, 'WWW host is unaffected');
$GLOBALS['g5_env']['site'] = 'http://dev.beyourlover.com';
expect($guard(false, [], 'https://api.automizely.com') === false, 'noncanonical HTTP Dev host is unaffected');
$GLOBALS['g5_env']['site'] = 'https://dev.beyourlover.com';
expect($guard(false, [], 123) === false, 'non-string URL is never interpreted as provider host');
expect(isset($GLOBALS['g5_hooks']['woocommerce_webhook_should_deliver'][PHP_INT_MAX]),
    'existing P7 native Woo order-webhook hook remains registered');
expect(isset($GLOBALS['g5_hooks']['pre_wp_mail'][99]),
    'existing Dev outbound email guard remains registered');
expect(isset($GLOBALS['g5_hooks']['rest_pre_dispatch'][99]),
    'existing Dev Store API Checkout fail-closed hook remains registered');
expect(isset($GLOBALS['g5_hooks']['woocommerce_available_payment_gateways'][99]),
    'existing Dev payment-gateway guard remains registered');
// G5 inbound: plugin rewrite routes, direct aliases, and exact REST route boundaries.
expect(isset($GLOBALS['g5_hooks']['parse_request'][-999]), 'early AfterShip ingress guard registered');
expect(isset($GLOBALS['g5_hooks']['rest_pre_dispatch'][98]), 'native order/customer ingress guard registered');
foreach ([
    ['/aftership-api/v3/orders', [], []],
    ['/aftership-api/v4/orders/20', [], []],
    ['/aftership-api/v5/orders', [], []],
    ['/wp-json/?aftership-api-route=/orders', [], ['aftership-api-route'=>'/orders']],
    ['/', ['aftership-api-route'=>'/orders'], []],
    ['/', [], ['aftership-api'=>'callback']],
    ['/shop/aftership-api/v4/orders', [], []],
] as $i => [$uri, $qvars, $q]) {
    expect(byl_dev_g5_aftership_inbound_path($uri, $qvars, $q) === true,
        'blocks incoming AfterShip endpoint or alias ' . $i);
}
foreach ([
    '/', '/checkout/', '/cart/', '/wp-json/wc/store/v1/cart',
    '/aftership-api.evil.example/', '/some/aftership-api-typo',
] as $i => $uri) {
    expect(byl_dev_g5_aftership_inbound_path($uri, [], []) === false,
        'preserves unrelated site request ' . $i);
}
$inbound = $GLOBALS['g5_hooks']['rest_pre_dispatch'][98][0]['callback'];
foreach ([
    '/wc/v1/orders', '/wc/v2/orders/20', '/wc/v3/orders/20/refunds',
    '/wc/v3/orders/batch', '/wc/v3/customers', '/wc/v3/customers/22',
] as $i => $route) {
    $r = $inbound(null, null, new WP_REST_Request($route));
    expect($r instanceof WP_Error
        && $r->get_error_code() === 'byl_dev_g5_provider_inbound_quarantine'
        && ($r->get_error_data()['status'] ?? null) === 403,
        'native Woo API order/customer access denied ' . $i);
}
foreach ([
    '/wc/store/v1/cart', '/wc/store/v1/products', '/wc/v3/products',
    '/wc-analytics/reports/orders', '/wp/v2/pages',
] as $i => $route) {
    expect($inbound(null, null, new WP_REST_Request($route)) === null,
        'non-target Woo/WP REST route preserved ' . $i);
}
$GLOBALS['g5_env']['site'] = 'https://www.beyourlover.com';
expect($inbound(null, null, new WP_REST_Request('/wc/v3/orders')) === null,
    'WWW native Woo Orders REST unaffected');
$GLOBALS['g5_env']['site'] = 'https://dev.beyourlover.com';
$GLOBALS['g5_env']['environment'] = 'production';
expect($inbound(null, null, new WP_REST_Request('/wc/v3/orders')) === null,
    'production environment native Woo Orders REST unaffected');
$GLOBALS['g5_env']['environment'] = 'staging';
$upstream = new WP_Error('prior_rest_denial');
expect($inbound($upstream, null, new WP_REST_Request('/wc/v3/orders')) === $upstream,
    'preserve earlier REST denial');
echo 'G5_AFTERSHIP_DEV_OUTBOUND_ISOLATION=PASS ' . $checks . ' offline checks; no HTTP / order / charge' . PHP_EOL;
echo 'G5_DEV_PROVIDER_BIDIRECTIONAL_QUARANTINE=PASS; no provider requests or order writes' . PHP_EOL;
