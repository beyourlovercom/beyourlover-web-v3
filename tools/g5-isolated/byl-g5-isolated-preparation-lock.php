<?php
/**
 * Plugin Name: BYL G5 Isolated UAT — Preparation Lock
 * Temporary isolated staging only. Never install on WWW or existing Dev.
 */
defined('ABSPATH') || exit;

if (wp_get_environment_type() !== 'staging'
    || home_url() !== 'https://g5-uat.beyourlover.com') {
    return;
}

// Both checkout entry points stay closed until a separate Provider transaction grant.
add_filter('woocommerce_available_payment_gateways', static fn (array $gateways): array => [], PHP_INT_MAX);
add_action('woocommerce_checkout_process', static function (): void {
    if (function_exists('wc_add_notice')) {
        wc_add_notice('G5 isolated payment testing is not authorized yet.', 'error');
    }
}, PHP_INT_MAX);

add_filter('rest_pre_dispatch', static function ($result, $server, $request) {
    if ($result !== null || !($request instanceof WP_REST_Request)) { return $result; }
    $route = (string) $request->get_route();
    if (preg_match('#^/wc/(?:v1|v2|v3)/orders(?:/|$)#', $route)
        || preg_match('#^/wc/store(?:/v1)?/checkout(?:/|$)#', $route)) {
        return new WP_Error('byl_g5_isolated_order_api_closed',
            'Order and payment APIs are disabled during isolated G5 preparation.',
            ['status' => 403]);
    }
    return $result;
}, PHP_INT_MAX, 3);

// Prohibit AfterShip's legacy incoming API on this temporary environment.
add_action('parse_request', static function (): void {
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
    $path = wp_parse_url($uri, PHP_URL_PATH);
    if (is_string($path) && preg_match('#(?:^|/)aftership-api(?:/|$)#i', $path)) {
        status_header(403);
        exit;
    }
}, -999);

// WordPress native Woo webhook, WordPress mail and ALL WP HTTP are disabled.
// Preserve preemptions made by other filters.
add_filter('woocommerce_webhook_should_deliver', static fn () => false, PHP_INT_MAX, 3);
add_filter('pre_wp_mail', static fn () => false, PHP_INT_MAX);
add_filter('pre_http_request', static function ($pre, $args, $url) {
    return $pre !== false ? $pre : new WP_Error(
        'byl_g5_isolated_network_closed',
        'G5 preparation: outgoing HTTP is disabled.'
    );
}, PHP_INT_MAX, 3);

add_action('send_headers', static function (): void {
    header('X-Robots-Tag: noindex, nofollow, noarchive', true);
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0', true);
});
add_filter('wp_robots', static function (array $robots): array {
    $robots['noindex'] = true;
    $robots['nofollow'] = true;
    $robots['noarchive'] = true;
    return $robots;
});
