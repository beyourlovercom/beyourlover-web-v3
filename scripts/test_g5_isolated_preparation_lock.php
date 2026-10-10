<?php
/** Offline nontransaction smoke for temporary G5 staging preparation lock. */
declare(strict_types=1);
define('ABSPATH', '/isolated-g5/');
$GLOBALS['hooks'] = [];
function add_filter($name, $callback, $priority = 10, $args = 1) {
    $GLOBALS['hooks'][$name][] = $callback;
}
function add_action($name, $callback, $priority = 10, $args = 1) {
    $GLOBALS['hooks'][$name][] = $callback;
}
function wp_get_environment_type() { return 'staging'; }
function home_url() { return $GLOBALS['g5_home'] ?? 'http://g5-uat.beyourlover.com'; }
function wc_add_notice($text,$type) { $GLOBALS['g5_notice'] = [$text,$type]; }
class WP_REST_Request {
    function __construct(private string $route){}
    function get_route() {return $this->route;}
}
class WP_Error {
    function __construct(private string $code,private string $message,private array $data=[]){}
    function get_error_code() {return $this->code;}
    function get_error_data() {return $this->data;}
}
function findhook(string $name) {
    $hooks=$GLOBALS['hooks'][$name]??[];
    if (count($hooks)!==1) throw new RuntimeException('hook absent or duplicated '.$name);
    return $hooks[0];
}
require dirname(__DIR__) . '/tools/g5-isolated/byl-g5-isolated-preparation-lock.php';
$pass=0;
function test($ok,$label) {
    global $pass;
    if (!$ok) throw new RuntimeException('FAIL '.$label);
    $pass++;
    echo 'PASS '.$label.PHP_EOL;
}
test(findhook('woocommerce_available_payment_gateways')(['test_card'=>'active'])===[], 'all gateways closed');
findhook('woocommerce_checkout_process')();
test(($GLOBALS['g5_notice'][1]??'')==='error', 'Woo native checkout rejects order');
$rest=findhook('rest_pre_dispatch');
foreach (['/wc/v3/orders','/wc/v3/orders/20','/wc/v2/orders','/wc/store/v1/checkout','/wc/store/checkout'] as $route) {
    $error=$rest(null, null, new WP_REST_Request($route));
    test($error instanceof WP_Error && $error->get_error_data()['status']===403, 'order API closed '.$route);
}
test($rest(null,null,new WP_REST_Request('/wc/store/v1/cart'))===null, 'read-only cart preserved');
test(findhook('woocommerce_webhook_should_deliver')(true,null,123)===false, 'native Woo webhook suppressed');
test(findhook('pre_wp_mail')(['to'=>'synthetic@example.invalid'])===false, 'email suppressed');
$http=findhook('pre_http_request');
test($http(false, [], 'https://api.stripe.com/v1/payment_intents')->get_error_code()==='byl_g5_isolated_network_closed', 'payment provider HTTP denied');
test($http(false, [], 'https://api.automizely.com/aftership/public/graphql')->get_error_code()==='byl_g5_isolated_network_closed', 'aftership HTTP denied');
$prior=new WP_Error('earlier_denied','prior');
test($http($prior, [], 'https://api.stripe.com/')===$prior,'prior network rejection preserved');
test(isset($GLOBALS['hooks']['parse_request']),'AfterShip legacy ingress registered');
test(isset($GLOBALS['hooks']['wp_robots']),'robots noindex registered');
// Canonical hostname and environment gate are part of fail-closed safety.
$source = file_get_contents(dirname(__DIR__) . '/tools/g5-isolated/byl-g5-isolated-preparation-lock.php');
test(str_contains($source, "'http://g5-uat.beyourlover.com'")
    && str_contains($source, "'https://g5-uat.beyourlover.com'"),
    'lock covers Hostinger HTTP transition and eventual HTTPS canonical URL');

echo "G5_ISOLATED_PREPARATION_LOCK_PASS=$pass checks, zero requests, zero orders".PHP_EOL;
