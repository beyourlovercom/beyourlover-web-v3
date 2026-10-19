<?php
/**
 * Editorial-only wrapper for WordPress Posts and category archives.
 *
 * Depends on the separately reviewed Blog WWW Parity V1 shell (PR #102).
 * Fail closed: preserve the existing standard theme header/footer when the
 * Blog shell is absent. Never modify Woo, Pages, order or payment routes.
 */
declare(strict_types=1);
defined('ABSPATH') || exit;

if (!function_exists('byl_blog_editorial_shell_ready')) {
    function byl_blog_editorial_shell_ready(): bool {
        $base = get_stylesheet_directory();
        return is_file($base . '/blog-parts/header.php')
            && is_file($base . '/blog-parts/footer.php')
            && is_file($base . '/assets/css/blog-parity-v1.css');
    }
    function byl_blog_editorial_header(): void {
        if (byl_blog_editorial_shell_ready()) {
            require get_stylesheet_directory() . '/blog-parts/header.php';
        } else {
            get_header();
        }
    }
    function byl_blog_editorial_footer(): void {
        if (byl_blog_editorial_shell_ready()) {
            require get_stylesheet_directory() . '/blog-parts/footer.php';
        } else {
            get_footer();
        }
    }
}
add_action('wp_enqueue_scripts', static function (): void {
    if (!byl_blog_editorial_shell_ready()) {
        return;
    }
    if (!((is_single() && get_post_type() === 'post') || is_category())) {
        return;
    }
    $base = get_stylesheet_directory();
    $uri = get_stylesheet_directory_uri();
    foreach ([
        ['kind'=>'css', 'path'=>'assets/css/blog-parity-v1.css', 'handle'=>'byl-blog-editorial-shell'],
        ['kind'=>'css', 'path'=>'assets/css/blog-archive-v1.css', 'handle'=>'byl-blog-editorial-archive'],
        ['kind'=>'js', 'path'=>'assets/js/blog-editorial-shell.js', 'handle'=>'byl-blog-editorial-shell-js'],
    ] as $asset) {
        $path = $base . '/' . $asset['path'];
        if (!is_file($path)) continue;
        $version = (string) filemtime($path);
        if ($asset['kind'] === 'css') {
            wp_enqueue_style($asset['handle'], $uri.'/'.$asset['path'], [], $version);
        } else {
            wp_enqueue_script($asset['handle'], $uri.'/'.$asset['path'], [], $version, true);
            wp_script_add_data($asset['handle'], 'strategy', 'defer');
        }
    }
}, 20);
