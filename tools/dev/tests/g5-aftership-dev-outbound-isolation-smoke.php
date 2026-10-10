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
function add_action($name, $callback, $priority = 10, $args = 1): void {}
function wp_get_environment_type(): string { return $GLOBALS['g5_env']['environment']; }
function home_url($path = ''): string { return $GLOBALS['g5_env']['site'] . $path; }
function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
class WP_Error {
    public function __construct(private string $code, private string $message = '') {}
    public function get_error_code(): string { return $this->code; }
    public function get_error_message(): string { return $this->message; }
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
echo 'G5_AFTERSHIP_DEV_OUTBOUND_ISOLATION=PASS ' . $checks . ' offline checks; no HTTP / order / charge' . PHP_EOL;
