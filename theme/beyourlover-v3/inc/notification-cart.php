<?php
/**
 * BYL Notification × Cart N6 isolated integration gate.
 * No global Woo notice replacement; no plugin or DB writes.
 */
declare(strict_types=1);
defined('ABSPATH') || exit;

function byl_v3_notification_cart_bridge_enabled(): bool {
    if (!function_exists('is_cart') || !is_cart()
        || !function_exists('byl_v3_cart_drawer_preview_enabled')
        || !byl_v3_cart_drawer_preview_enabled()) { return false; }
    $host = (string) wp_parse_url(home_url('/'), PHP_URL_HOST);
    return $host === 'dev.beyourlover.com'
        && isset($_GET['byl_notification_bridge'])
        && is_string($_GET['byl_notification_bridge'])
        && (string) wp_unslash($_GET['byl_notification_bridge']) === '1';
}

/** Dev-only empty cart presentation; separate from N6 notification preview. */
function byl_v3_cart_empty_page_parity_enabled(): bool {
    return function_exists('is_cart') && is_cart()
        && function_exists('byl_v3_cart_drawer_runtime_enabled')
        && byl_v3_cart_drawer_runtime_enabled();
}

add_action('wp_enqueue_scripts', static function (): void {
    if (!byl_v3_cart_empty_page_parity_enabled()) { return; }
    $path = get_stylesheet_directory().'/assets/css/cart-empty-parity.css';
    wp_enqueue_style('byl-v3-cart-empty-parity',
        get_stylesheet_directory_uri().'/assets/css/cart-empty-parity.css',
        ['byl-v3-cart-focus'], (string) filemtime($path));
}, 26);

/** Enqueue only with both explicitly enabled Dev preview gates. */
add_action('wp_enqueue_scripts', static function (): void {
    if (!byl_v3_notification_cart_bridge_enabled()) { return; }
    $dir = get_stylesheet_directory();
    $uri = get_stylesheet_directory_uri();
    $version = static function (string $p) use ($dir): string {
        return (string) filemtime($dir.'/'.$p);
    };
    wp_enqueue_style('byl-v3-notification-ui', $uri.'/assets/css/notification.css',
        ['byl-v3-cart-focus'], $version('assets/css/notification.css'));
    wp_enqueue_style('byl-v3-notification-surfaces', $uri.'/assets/css/notification-surfaces.css',
        ['byl-v3-notification-ui'], $version('assets/css/notification-surfaces.css'));
    wp_enqueue_style('byl-v3-notification-woo', $uri.'/assets/css/notification-woo-adapter.css',
        ['byl-v3-notification-surfaces'], $version('assets/css/notification-woo-adapter.css'));
    wp_enqueue_script('byl-v3-notification', $uri.'/assets/js/notification.js', [],
        $version('assets/js/notification.js'), true);
    wp_enqueue_script('byl-v3-notification-cart-bridge', $uri.'/assets/js/notification-cart-bridge.js',
        ['byl-v3-notification'], $version('assets/js/notification-cart-bridge.js'), true);
    wp_script_add_data('byl-v3-notification', 'strategy', 'defer');
    wp_script_add_data('byl-v3-notification-cart-bridge', 'strategy', 'defer');
}, 25);

add_filter('body_class', static function (array $classes): array {
    if (byl_v3_notification_cart_bridge_enabled()) {
        $classes[] = 'byl-n6-notification-preview';
    }
    return $classes;
}, 20);

/**
 * Preserve core Woo template for every normal request; isolate the new empty state
 * behind Dev + Cart Drawer + Notification flags. Do not mutate cart/session.
 */
add_filter('woocommerce_locate_template', static function (
    string $located, string $template_name, string $template_path
): string {
    if ($template_name !== 'cart/cart-empty.php' || !byl_v3_cart_empty_page_parity_enabled()) {
        return $located;
    }
    $candidate = get_stylesheet_directory().'/inc/notification-cart-empty.php';
    return is_file($candidate) ? $candidate : $located;
}, 20, 3);
