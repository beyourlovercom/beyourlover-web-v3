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
    $version = wp_get_theme()->get('Version');
    $uri = get_stylesheet_directory_uri();
    wp_enqueue_style('byl-v3-foundation', get_stylesheet_uri(), [], $version);
    wp_enqueue_style('byl-v3-tokens', $uri . '/assets/css/tokens.css', ['byl-v3-foundation'], $version);
    wp_enqueue_style('byl-v3-components', $uri . '/assets/css/components.css', ['byl-v3-tokens'], $version);
    if (is_front_page()) {
        wp_enqueue_style('byl-v3-preview', $uri . '/assets/css/preview.css', ['byl-v3-components'], $version);
    }
});
