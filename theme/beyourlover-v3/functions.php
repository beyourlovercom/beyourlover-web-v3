<?php
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

add_action('after_setup_theme', static function (): void {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('woocommerce');
    add_theme_support('custom-logo');
    add_theme_support('html5', ['search-form','comment-form','comment-list','gallery','caption','style','script']);
    register_nav_menus(['primary' => 'Global navigation', 'footer' => 'Footer navigation']);
});

function byl_v3_primary_nav_items(): array {
    return apply_filters('byl_v3_primary_nav_items', [
        ['label' => 'Products', 'url' => home_url('/shop/')],
        ['label' => 'Explore', 'url' => home_url('/best-sellers/')],
        ['label' => 'App', 'url' => home_url('/app-guide/')],
        ['label' => 'Learn', 'url' => home_url('/blog/')],
        ['label' => 'Support', 'url' => home_url('/customer-help/')],
    ]);
}

function byl_v3_brand_logo(): string {
    foreach (['logo-slogan-red-1-scaled','logo-slogan-red-1'] as $slug) {
        $attachment = get_page_by_path($slug, OBJECT, 'attachment');
        if ($attachment instanceof WP_Post) {
            $url = wp_get_attachment_image_url($attachment->ID, 'full');
            if ($url) { return '<img class="byl-brand-logo" src="'.esc_url($url).'" alt="BeYourLover®">'; }
        }
    }
    return '<span class="byl-brand-fallback"><span class="byl-brand-dot" aria-hidden="true"></span>BEYOURLOVER</span>';
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
    wp_enqueue_style('byl-v3-shell', $uri.'/assets/css/shell.css', ['byl-v3-components'], $v('assets/css/shell.css'));
    wp_enqueue_style('byl-v3-motion', $uri.'/assets/css/motion.css', ['byl-v3-shell'], $v('assets/css/motion.css'));
    wp_enqueue_script('byl-v3-shell', $uri.'/assets/js/shell.js', [], $v('assets/js/shell.js'), true);
    wp_script_add_data('byl-v3-shell', 'strategy', 'defer');
    if (is_front_page()) { wp_enqueue_style('byl-v3-preview', $uri.'/assets/css/preview.css', ['byl-v3-motion'], $v('assets/css/preview.css')); }
});

add_filter('body_class', static function (array $classes): array { $classes[]='byl-shell-active'; return $classes; });
