<?php
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }
require_once get_stylesheet_directory().'/inc/components.php';
require_once get_stylesheet_directory().'/inc/shell-profiles.php';
require_once get_stylesheet_directory().'/inc/homepage.php';
require_once get_stylesheet_directory().'/inc/archive.php';
require_once get_stylesheet_directory().'/inc/product.php';
require_once get_stylesheet_directory().'/inc/product-modules.php';
require_once get_stylesheet_directory().'/inc/checkout.php';

add_action('after_setup_theme', static function (): void {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('woocommerce');
    add_theme_support('custom-logo');
    add_theme_support('html5', ['search-form','comment-form','comment-list','gallery','caption','style','script']);
    register_nav_menus(['primary' => 'Global navigation', 'mobile' => 'Mobile navigation', 'footer' => 'Footer navigation']);
});

function byl_v3_legacy_nav_locations(): array {
    $mods = get_option('theme_mods_shoptimizer-child-theme', []);
    return is_array($mods) && isset($mods['nav_menu_locations']) && is_array($mods['nav_menu_locations']) ? $mods['nav_menu_locations'] : [];
}

function byl_v3_menu_id(string $location): int {
    $locations = get_nav_menu_locations();
    if (!empty($locations[$location])) { return (int) $locations[$location]; }
    $legacy = byl_v3_legacy_nav_locations();
    if (!empty($legacy[$location])) { return (int) $legacy[$location]; }
    if ($location === 'mobile' && !empty($locations['primary'])) { return (int) $locations['primary']; }
    if ($location === 'mobile' && !empty($legacy['primary'])) { return (int) $legacy['primary']; }
    return 0;
}

function byl_v3_seed_nav_locations_from_legacy(): void {
    $current = get_theme_mod('nav_menu_locations', []);
    $current = is_array($current) ? $current : [];
    $legacy = byl_v3_legacy_nav_locations();
    $changed = false;
    foreach (['primary', 'mobile'] as $location) {
        if (empty($current[$location]) && !empty($legacy[$location])) {
            $current[$location] = (int) $legacy[$location];
            $changed = true;
        }
    }
    if ($changed) { set_theme_mod('nav_menu_locations', $current); }
}
add_action('after_switch_theme', 'byl_v3_seed_nav_locations_from_legacy');

function byl_v3_fallback_nav_items(): array {
    return [
        ['label' => 'New Arrivals', 'url' => home_url('/new-arrivals/')],
        ['label' => 'Categories', 'url' => home_url('/sex-toys/')],
        ['label' => 'Best Sellers', 'url' => home_url('/best-sellers/')],
        ['label' => 'Apps', 'url' => home_url('/app-guide/')],
        ['label' => 'Blog', 'url' => home_url('/blog/')],
        ['label' => 'Support', 'url' => home_url('/customer-help/')],
    ];
}

function byl_v3_attachment_url(int $attachment_id): string {
    $offloaded = (string) get_post_meta($attachment_id, 'advmo_offloaded', true);
    $path = trim((string) get_post_meta($attachment_id, 'advmo_path', true), '/');
    $attached = (string) get_post_meta($attachment_id, '_wp_attached_file', true);
    if ($offloaded === '1' && $path !== '' && $attached !== '') {
        return 'https://img.beyourlover.com/'.trailingslashit($path).rawurlencode(wp_basename($attached));
    }
    $url = wp_get_attachment_image_url($attachment_id, 'full');
    return is_string($url) ? $url : '';
}

function byl_v3_brand_logo(): string {
    foreach (['logo-slogan-red-1-scaled-webp','logo-slogan-red-1-scaled-1-webp','logo-slogan-red-1-scaled','logo-slogan-red-1'] as $slug) {
        $attachment = get_page_by_path($slug, OBJECT, 'attachment');
        if ($attachment instanceof WP_Post) {
            $url = byl_v3_attachment_url((int) $attachment->ID);
            if ($url !== '') { return '<img class="byl-brand-logo" src="'.esc_url($url).'" alt="BeYourLover®">'; }
        }
    }
    return '<span class="byl-brand-fallback"><span class="byl-brand-dot" aria-hidden="true"></span>BeYourLover</span>';
}

function byl_v3_account_url(): string {
    return function_exists('wc_get_page_permalink') ? (string) wc_get_page_permalink('myaccount') : home_url('/my-account/');
}
function byl_v3_cart_url(): string {
    return function_exists('wc_get_cart_url') ? (string) wc_get_cart_url() : home_url('/cart/');
}
function byl_v3_cart_count(): int {
    return function_exists('WC') && WC() && WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
}
function byl_v3_context_nav(): array {
    return (array) apply_filters('byl_v3_context_nav', []);
}

function byl_v3_nav_item_is_current(array $item): bool {
    $target = wp_parse_url((string)($item['url'] ?? ''), PHP_URL_PATH);
    $current = wp_parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    if (!is_string($target) || !is_string($current)) { return false; }
    $target = untrailingslashit($target) ?: '/';
    $current = untrailingslashit($current) ?: '/';
    return $target === $current;
}

add_action('wp_enqueue_scripts', static function (): void {
    $theme_version = wp_get_theme()->get('Version');
    $dir = get_stylesheet_directory(); $uri = get_stylesheet_directory_uri();
    $v = static function (string $r) use ($dir, $theme_version): string { $p=$dir.'/'.ltrim($r,'/'); return is_file($p)?(string)filemtime($p):(string)$theme_version; };
    wp_enqueue_style('byl-v3-foundation', get_stylesheet_uri(), [], $v('style.css'));
    wp_enqueue_style('byl-v3-tokens', $uri.'/assets/css/tokens.css', ['byl-v3-foundation'], $v('assets/css/tokens.css'));
    wp_enqueue_style('byl-v3-components', $uri.'/assets/css/components.css', ['byl-v3-tokens'], $v('assets/css/components.css'));
    wp_enqueue_style('byl-v3-component-library', $uri.'/assets/css/component-library.css', ['byl-v3-components'], $v('assets/css/component-library.css'));
    wp_enqueue_style('byl-v3-shell', $uri.'/assets/css/shell.css', ['byl-v3-component-library'], $v('assets/css/shell.css'));
    wp_enqueue_style('byl-v3-motion', $uri.'/assets/css/motion.css', ['byl-v3-shell'], $v('assets/css/motion.css'));
    wp_enqueue_script('byl-v3-shell', $uri.'/assets/js/shell.js', [], $v('assets/js/shell.js'), true);
    wp_script_add_data('byl-v3-shell', 'strategy', 'defer');
    if (is_front_page()) { wp_enqueue_style('byl-v3-homepage', $uri.'/assets/css/homepage.css', ['byl-v3-motion'], $v('assets/css/homepage.css')); }
    if (function_exists('is_shop') && (is_shop() || (function_exists('is_product_taxonomy') && is_product_taxonomy()))) { wp_enqueue_style('byl-v3-archive', $uri.'/assets/css/archive.css', ['byl-v3-motion'], $v('assets/css/archive.css')); }
    if (function_exists('is_product') && is_product()) {
        wp_enqueue_style('byl-v3-product', $uri.'/assets/css/product.css', ['byl-v3-motion'], $v('assets/css/product.css'));
        wp_enqueue_script('byl-v3-product', $uri.'/assets/js/product.js', [], $v('assets/js/product.js'), true);
        wp_script_add_data('byl-v3-product', 'strategy', 'defer');
    }
    if (function_exists('is_cart') && is_cart()) {
        wp_enqueue_style('byl-v3-cart', $uri.'/assets/css/cart.css', ['byl-v3-motion'], $v('assets/css/cart.css'));
        wp_enqueue_script('byl-v3-cart', $uri.'/assets/js/cart.js', ['wp-data', 'wc-blocks-data-store'], $v('assets/js/cart.js'), true);
        wp_localize_script('byl-v3-cart', 'BYLShippingPromotionCart', [
            'applyUrl' => function_exists('rest_url') ? rest_url('wc/store/v1/cart/apply-coupon') : '',
            'removeUrl' => function_exists('rest_url') ? rest_url('wc/store/v1/cart/remove-coupon') : '',
            'nonce' => function_exists('wp_create_nonce') ? wp_create_nonce('wc_store_api') : '',
        ]);
        wp_script_add_data('byl-v3-cart', 'strategy', 'defer');
    }
    if (function_exists('is_checkout') && is_checkout() && !is_order_received_page()) {
        wp_enqueue_style('byl-v3-checkout', $uri.'/assets/css/checkout.css', ['byl-v3-motion'], $v('assets/css/checkout.css'));
        $checkout_dependencies = ['jquery'];
        if (wp_script_is('wc-checkout', 'registered') || wp_script_is('wc-checkout', 'enqueued')) {
            $checkout_dependencies[] = 'wc-checkout';
        }
        wp_enqueue_script('byl-v3-checkout', $uri.'/assets/js/checkout.js', $checkout_dependencies, $v('assets/js/checkout.js'), true);
        wp_localize_script('byl-v3-checkout', 'BYLShippingPromotionCheckout', [
            'cartUrl' => function_exists('rest_url') ? rest_url('wc/store/v1/cart') : '',
            'applyUrl' => function_exists('rest_url') ? rest_url('wc/store/v1/cart/apply-coupon') : '',
            'removeUrl' => function_exists('rest_url') ? rest_url('wc/store/v1/cart/remove-coupon') : '',
            'nonce' => function_exists('wp_create_nonce') ? wp_create_nonce('wc_store_api') : '',
        ]);
        wp_script_add_data('byl-v3-checkout', 'strategy', 'defer');
    }
});

add_filter('body_class', static function (array $classes): array {
    $classes[] = 'byl-shell-active';
    $header = byl_v3_current_header_profile();
    $footer = byl_v3_current_footer_profile();
    $classes[] = 'byl-header-profile-'.sanitize_html_class($header);
    $classes[] = 'byl-footer-profile-'.sanitize_html_class($footer);
    if (byl_v3_shell_overlay_enabled($header)) { $classes[] = 'byl-shell-overlay'; }
    return $classes;
});
