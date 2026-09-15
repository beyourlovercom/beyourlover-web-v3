<?php
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

add_action('after_setup_theme', static function (): void {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('woocommerce');
    add_theme_support('custom-logo');
    add_theme_support('html5', ['search-form','comment-form','comment-list','gallery','caption','style','script']);
});

add_action('wp_enqueue_scripts', static function (): void {
    $theme_version = wp_get_theme()->get('Version');
    $dir = get_stylesheet_directory();
    $uri = get_stylesheet_directory_uri();
    $asset_version = static function (string $relative) use ($dir, $theme_version): string {
        $path = $dir . '/' . ltrim($relative, '/');
        return is_file($path) ? (string) filemtime($path) : (string) $theme_version;
    };

    wp_enqueue_style('byl-v3-foundation', get_stylesheet_uri(), [], $asset_version('style.css'));
    wp_enqueue_style('byl-v3-tokens', $uri . '/assets/css/tokens.css', ['byl-v3-foundation'], $asset_version('assets/css/tokens.css'));
    wp_enqueue_style('byl-v3-components', $uri . '/assets/css/components.css', ['byl-v3-tokens'], $asset_version('assets/css/components.css'));
    wp_enqueue_style('byl-v3-motion', $uri . '/assets/css/motion.css', ['byl-v3-components'], $asset_version('assets/css/motion.css'));

    if (is_front_page()) {
        wp_enqueue_style('byl-v3-preview', $uri . '/assets/css/preview.css', ['byl-v3-motion'], $asset_version('assets/css/preview.css'));
    }
});
