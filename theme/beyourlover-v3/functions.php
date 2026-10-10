<?php
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }
require_once get_stylesheet_directory().'/inc/theme-os-bootstrap.php';

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

require_once get_stylesheet_directory().'/inc/theme-os-assets.php';

require_once get_stylesheet_directory().'/inc/free-shipping-v2-policy.php';

/**
 * Free Shipping page URL compatibility.
 *
 * A preexisting Dev SVG media attachment uses the slug `free-shipping`.
 * Never alter/delete the attachment or bypass WordPress slug uniqueness.
 * The official Page is stored as `free-shipping-guide`, while its single
 * public address stays /free-shipping/ via an exact-path read-only router.
 */
function byl_v3_is_free_shipping_route(): bool {
    $path = wp_parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    return is_string($path) && (untrailingslashit($path) ?: '/') === '/free-shipping';
}
add_filter('request', static function (array $vars): array {
    if (!byl_v3_is_free_shipping_route()) { return $vars; }
    $page = get_page_by_path('free-shipping-guide', OBJECT, 'page');
    if (!$page instanceof WP_Post || $page->post_status !== 'publish') { return $vars; }
    return ['page_id' => (int)$page->ID];
}, 8);
add_filter('redirect_canonical', static function ($redirect) {
    return byl_v3_is_free_shipping_route() ? false : $redirect;
});
// Permalink Manager Pro owns an additional redirect path outside WordPress
// redirect_canonical. Preserve the public /free-shipping/ URL only; all other
// plugin redirects are untouched.
add_filter('permalink_manager_filter_redirect', static function ($url) {
    return byl_v3_is_free_shipping_route() ? null : $url;
}, 20, 1);
add_filter('page_link', static function (string $link, int $post_id): string {
    if (get_post_field('post_name', $post_id) === 'free-shipping-guide') {
        return home_url('/free-shipping/');
    }
    return $link;
}, 10, 2);

// Presentation-only Woo labels on this single page; Woo still owns form submission and validation.
add_filter('gettext', static function (string $translated, string $original, string $domain): string {
    if ($domain !== 'woocommerce' || !is_page('track-your-order')) { return $translated; }
    if ($original === 'Track') { return __('Track Order', 'beyourlover-v3'); }
    if ($original === 'Order ID') { return __('Order number', 'beyourlover-v3'); }
    return $translated;
}, 10, 3);

add_filter('body_class', static function (array $classes): array {
    $classes[] = 'byl-shell-active';
    $header = byl_v3_current_header_profile();
    $footer = byl_v3_current_footer_profile();
    $classes[] = 'byl-header-profile-'.sanitize_html_class($header);
    $classes[] = 'byl-footer-profile-'.sanitize_html_class($footer);
    if (byl_v3_shell_overlay_enabled($header)) { $classes[] = 'byl-shell-overlay'; }
    return $classes;
});
