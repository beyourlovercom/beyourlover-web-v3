<?php
/**
 * BYL Dev-only A1 external Woo product REST quarantine.
 *
 * Blocks ERP/automation REST reads and mutations of Dev Woo product
 * endpoints even if an old Woo API consumer key still authenticates.
 * Keeps Store API for storefront reads and trusted nonce+cookie WP-admin
 * product editor operations functional. Never loads on WWW/production.
 *
 * DO NOT use this as proof that independent external ERP/warehouse
 * polling or non-WP HTTP clients are isolated.
 */
defined('ABSPATH') || exit;

function byl_a1_dev_product_rest_isolation_site(): bool {
    return function_exists('wp_get_environment_type')
        && wp_get_environment_type() === 'staging'
        && function_exists('home_url')
        && untrailingslashit((string) home_url()) === 'https://dev.beyourlover.com';
}

function byl_a1_dev_external_product_route(string $route): bool {
    return preg_match('#^/wc/(?:v1|v2|v3)/products(?:/|$)#D', $route) === 1;
}

function byl_a1_dev_cookie_admin_rest_nonce($request): bool {
    if (!($request instanceof WP_REST_Request)
        || !function_exists('is_user_logged_in')
        || !is_user_logged_in()
        || !function_exists('current_user_can')
        || !current_user_can('manage_woocommerce')
        || !defined('LOGGED_IN_COOKIE')
        || !isset($_COOKIE[LOGGED_IN_COOKIE])
        || !is_string($_COOKIE[LOGGED_IN_COOKIE])) {
        return false;
    }
    $nonce = $request->get_header('X-WP-Nonce');
    return is_string($nonce) && strlen($nonce) >= 8
        && function_exists('wp_verify_nonce')
        && (bool) wp_verify_nonce($nonce, 'wp_rest');
}

add_filter('rest_pre_dispatch', static function ($response, $server, $request) {
    if ($response !== null
        || !byl_a1_dev_product_rest_isolation_site()
        || !($request instanceof WP_REST_Request)
        || !byl_a1_dev_external_product_route((string) $request->get_route())) {
        return $response;
    }
    if (byl_a1_dev_cookie_admin_rest_nonce($request)) {
        return $response;
    }
    return new WP_Error(
        'byl_a1_dev_external_product_rest_quarantined',
        'External Woo product REST is quarantined on Dev.',
        ['status' => 403]
    );
}, 96, 3);
