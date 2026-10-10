<?php
/** Non-network test of the one-hour WordPress.com Test Account connection gate. */
declare(strict_types=1);
define('ABSPATH', '/g5-connect-only/');
define('BYL_G5_TEST_ACCOUNT_CONNECT_ONLY', true);
define('BYL_G5_TEST_ACCOUNT_CONNECT_UNTIL', time() + 1800);
$GLOBALS['hooks'] = [];
function add_filter($name, $callback, $priority = 10, $args = 1) {
    $GLOBALS['hooks'][$name][] = $callback;
}
function add_action($name, $callback, $priority = 10, $args = 1) {
    $GLOBALS['hooks'][$name][] = $callback;
}
function wp_get_environment_type() { return 'staging'; }
function home_url() { return 'https://g5-uat.beyourlover.com'; }
function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
class WP_Error {
    public function __construct(private string $code, private string $message, private array $data=[]) {}
    public function get_error_code() { return $this->code; }
    public function get_error_data() { return $this->data; }
}
class WP_REST_Request {
    public function __construct(private string $route) {}
    public function get_route() { return $this->route; }
}
function wc_add_notice($text, $type) { $GLOBALS['notice'] = $type; }
require dirname(__DIR__) . '/tools/g5-isolated/byl-g5-isolated-preparation-lock.php';
function hook(string $name) {
    $a=$GLOBALS['hooks'][$name]??[];
    if (count($a) !== 1) throw new RuntimeException('Unexpected hook count '.$name);
    return $a[0];
}
$count = 0;
function ok(bool $condition, string $label) {
    global $count;
    if (!$condition) throw new RuntimeException('FAIL '.$label);
    echo 'PASS '.$label.PHP_EOL;
    $count++;
}
$http=hook('pre_http_request');
foreach ([
    'https://public-api.wordpress.com/rest/v1.1/wcpay/',
    'https://wordpress.com/jetpack/connect/',
    'https://api.wordpress.com/rest/v1.2/',
] as $url) {
    ok($http(false, [], $url) === false, 'bounded WordPress.com connect-only endpoint');
}
foreach ([
    'http://public-api.wordpress.com/',
    'https://public-api.wordpress.com.evil.example/',
    'https://public-api.wordpress.com:8443.evil.example/',
    'https://api.stripe.com/v1/payment_intents',
    'https://js.stripe.com/v3',
    'https://api.automizely.com/graphql',
    'https://api.aftership.com/v4/trackings',
    'https://api.woocommerce.com/',
    'https://example.org/',
] as $url) {
    $r=$http(false, [], $url);
    ok($r instanceof WP_Error && $r->get_error_code() === 'byl_g5_isolated_network_closed',
        'all nonauthorized transport remains denied');
}
$prior=new WP_Error('prior_deny', 'Earlier');
ok($http($prior, [], 'https://public-api.wordpress.com') === $prior, 'preserve earlier filter denial');
ok(hook('woocommerce_available_payment_gateways')(['card'=>'active'])===[], 'gateway checkout remains closed');
hook('woocommerce_checkout_process')();
ok(($GLOBALS['notice'] ?? null)==='error', 'classic order submit still blocked');
$rest=hook('rest_pre_dispatch');
$r=$rest(null,null,new WP_REST_Request('/wc/store/v1/checkout'));
ok($r instanceof WP_Error && ($r->get_error_data()['status']??null)===403, 'Store API checkout still blocked');
$r=$rest(null,null,new WP_REST_Request('/wc/v3/orders'));
ok($r instanceof WP_Error && ($r->get_error_data()['status']??null)===403, 'Woo REST order API still blocked');
ok(hook('woocommerce_webhook_should_deliver')(true)===false, 'outgoing native webhook denied');
ok(hook('pre_wp_mail')(['to'=>'nobody@example.invalid'])===false, 'all email remains denied');
ok(isset($GLOBALS['hooks']['parse_request']), 'AfterShip legacy inbound API shield preserved');
echo "G5_WCPAY_CONNECT_ONLY_OFFLINE_PASS=$count; ZERO HTTP, ZERO ORDER, ZERO PAYMENT".PHP_EOL;
