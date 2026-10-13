<?php
/** Compact Persistent Notice C1: Dev-only Cart opt-in, visual styling only. */
declare(strict_types=1);
defined('ABSPATH') || exit;

function byl_v3_compact_cart_preview_enabled(): bool {
    if (!function_exists('is_cart') || !is_cart()
        || (string) wp_parse_url(home_url('/'), PHP_URL_HOST) !== 'dev.beyourlover.com'
        || strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'GET'
        || (function_exists('is_admin') && is_admin())
        || (function_exists('wp_doing_ajax') && wp_doing_ajax())) { return false; }
    $flag = $_GET['byl_compact_notice_preview'] ?? null;
    if (!is_string($flag) || (string) wp_unslash($flag) !== 'cart') { return false; }
    // Never combine with separately gated N6 Woo Notice adapter.
    if (function_exists('byl_v3_notification_cart_bridge_enabled')
        && byl_v3_notification_cart_bridge_enabled()) { return false; }
    return true;
}

add_action('wp_enqueue_scripts', static function (): void {
    if (!byl_v3_compact_cart_preview_enabled()) { return; }
    $dir = get_stylesheet_directory();
    $uri = get_stylesheet_directory_uri();
    $files = [
        'byl-v3-compact-persistent' => 'assets/css/notification-persistent-compact.css',
        'byl-v3-compact-cart' => 'assets/css/notification-compact-cart.css',
    ];
    foreach ($files as $file) { if (!is_file($dir.'/'.$file)) { return; } }
    wp_enqueue_style('byl-v3-compact-persistent', $uri.'/'.$files['byl-v3-compact-persistent'], [],
        (string) filemtime($dir.'/'.$files['byl-v3-compact-persistent']));
    wp_enqueue_style('byl-v3-compact-cart', $uri.'/'.$files['byl-v3-compact-cart'],
        ['byl-v3-compact-persistent'], (string) filemtime($dir.'/'.$files['byl-v3-compact-cart']));
}, 26);

add_filter('body_class', static function (array $classes): array {
    if (byl_v3_compact_cart_preview_enabled()) { $classes[] = 'byl-cpn-cart-preview'; }
    return $classes;
}, 26);
