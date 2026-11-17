<?php
/**
 * Plugin Name: BYL Dev C8 Safe Old Slug Redirects
 * Description: Exact Dev-only 301 redirects for seven verified, unambiguous legacy article slugs.
 * Version: 1.0.0
 * Author: BYL
 *
 * This allowlist deliberately excludes duplicate old-slug groups and all product
 * records. It never enables Permalink Manager's global old-slug redirect switch.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (
    !function_exists('wp_get_environment_type')
    || wp_get_environment_type() !== 'staging'
    || !function_exists('home_url')
    || untrailingslashit(home_url()) !== 'https://dev.beyourlover.com'
) {
    return;
}

add_action('template_redirect', static function (): void {
    if (is_admin() || (function_exists('wp_doing_ajax') && wp_doing_ajax()) || (function_exists('wp_doing_cron') && wp_doing_cron()) || !is_404()) {
        return;
    }

    $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
    $request_path = wp_parse_url($request_uri, PHP_URL_PATH);

    if (!is_string($request_path) || $request_path === '') {
        return;
    }

    // Normalize only the trailing slash; the path must still match exactly.
    $normalized_path = '/' . trim($request_path, '/') . '/';

    $redirects = [
        '/blog/beyourlover-unveils-its-magic-suitcase-the-perfect-match-for-the-vibrant-energy-of-spring/' => '/blog/beyourlover-launches-magic-suitcase/',
        '/blog/cunnilingus-for-beginners-your-guide-to-oral-intimacy-and-pleasure/' => '/blog/how-to-give-cunnilingus/',
        '/blog/from-sweet-to-wild-10-must-try-lesbian-sex-positions/' => '/blog/10-lesbian-sex-positions/',
        '/blog/how-to-have-nipple-orgasm/' => '/blog/how-to-get-nipple-orgasm/',
        '/blog/is-fingering-yourself-normal-a-guide-to-self-exploration/' => '/blog/is-fingering-yourself-normal/',
        '/blog/is-it-safe-to-masturbate-on-period-2/' => '/blog/is-it-safe-to-masturbate-on-period/',
        '/blog/sideways-sex-position-pleasure-without-breaking-a-sweat/' => '/blog/sideway-sex-position/',
    ];

    if (!isset($redirects[$normalized_path])) {
        return;
    }

    $target = home_url($redirects[$normalized_path]);

    if (!empty($_SERVER['QUERY_STRING'])) {
        $target .= '?' . wp_unslash((string) $_SERVER['QUERY_STRING']);
    }

    wp_safe_redirect($target, 301, 'BYL Dev C8 Safe Old Slug Redirects');
    exit;
}, 1);
