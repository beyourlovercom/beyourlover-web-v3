<?php
/**
 * C2 Checkout Compact Persistent Notice — GET-only Dev opt-in.
 * Only presentation styles; Woo owns every notice and checkout rule.
 */
declare(strict_types=1);
defined('ABSPATH') || exit;

function byl_v3_compact_checkout_preview_enabled(): bool {
    if (!function_exists('home_url') || !function_exists('is_checkout')
        || !function_exists('is_checkout_pay_page')
        || !function_exists('is_order_received_page')
        || !is_checkout() || is_checkout_pay_page() || is_order_received_page()
        || (function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('order-pay'))
        || (string) wp_parse_url(home_url('/'), PHP_URL_HOST) !== 'dev.beyourlover.com'
        || strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'GET'
        || (function_exists('is_admin') && is_admin())
        || (function_exists('wp_doing_ajax') && wp_doing_ajax())
        || (defined('REST_REQUEST') && REST_REQUEST)) {
        return false;
    }
    $flag = $_GET['byl_compact_notice_preview'] ?? null;
    return is_string($flag) && (string) wp_unslash($flag) === 'checkout';
}

add_action('wp_enqueue_scripts', static function (): void {
    if (!byl_v3_compact_checkout_preview_enabled()) { return; }
    $dir = get_stylesheet_directory();
    $uri = get_stylesheet_directory_uri();
    $base = 'assets/css/notification-persistent-compact.css';
    $route = 'assets/css/notification-compact-checkout.css';
    if (!is_file($dir.'/'.$base) || !is_file($dir.'/'.$route)) { return; }
    wp_enqueue_style('byl-v3-compact-persistent', $uri.'/'.$base,
        ['byl-v3-checkout-shell'], (string) filemtime($dir.'/'.$base));
    wp_enqueue_style('byl-v3-compact-checkout', $uri.'/'.$route,
        ['byl-v3-compact-persistent'], (string) filemtime($dir.'/'.$route));
}, 26);

add_filter('body_class', static function (array $classes): array {
    if (byl_v3_compact_checkout_preview_enabled()) { $classes[] = 'byl-cpn-checkout-preview'; }
    return $classes;
}, 26);
