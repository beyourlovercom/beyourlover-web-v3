<?php
/**
 * H6.3-P: preserve the published Privacy Policies Page as the only canonical.
 *
 * This is a V3-theme compatibility redirect, not a WordPress rewrite rule.
 * It cannot and must not alter the current legacy Shoptimizer WWW runtime.
 */
declare(strict_types=1);
defined('ABSPATH') || exit;

/**
 * Match only the historic, misspelled literal URL, with no query payload.
 *
 * Never redirect decoded lookalikes, POST, REST, checkout callbacks, or
 * requests with query data that might contain customer or payment tokens.
 */
function byl_v3_privacy_alias_matches(string $request_uri, string $request_method): bool {
    if ($request_method !== 'GET' && $request_method !== 'HEAD') {
        return false;
    }
    return $request_uri === '/privacy-polices/' || $request_uri === '/privacy-polices';
}

/**
 * Early frontend-only 301; WordPress redirect_canonical runs later at 10.
 * Fail closed if the real public privacy Page isn't actually published.
 */
function byl_v3_privacy_alias_redirect(): void {
    if ((function_exists('is_admin') && is_admin())
        || (function_exists('wp_doing_ajax') && wp_doing_ajax())
        || (function_exists('wp_doing_cron') && wp_doing_cron())
        || (defined('REST_REQUEST') && REST_REQUEST)
        || (defined('WP_CLI') && WP_CLI)) {
        return;
    }

    $uri = $_SERVER['REQUEST_URI'] ?? null;
    $method = $_SERVER['REQUEST_METHOD'] ?? null;
    if (!is_string($uri) || !is_string($method)
        || !byl_v3_privacy_alias_matches($uri, $method)) {
        return;
    }

    $page = get_page_by_path('privacy-policies', OBJECT, 'page');
    if (!$page instanceof WP_Post
        || $page->post_status !== 'publish'
        || $page->post_name !== 'privacy-policies') {
        return;
    }
    // WordPress core privacy-page ownership must not point elsewhere.
    $privacy_page_id = (int)get_option('wp_page_for_privacy_policy', 0);
    if ($privacy_page_id > 0 && $privacy_page_id !== (int)$page->ID) {
        return;
    }

    // Never forward query strings or referer data. Do not infer a slug.
    $destination = home_url('/privacy-policies/');
    $base = wp_parse_url(home_url('/'));
    $target = wp_parse_url($destination);
    if (!is_array($base) || !is_array($target)
        || !isset($base['host'], $base['scheme'], $target['host'], $target['scheme'])
        || !hash_equals(strtolower((string)$base['host']), strtolower((string)$target['host']))
        || !hash_equals(strtolower((string)$base['scheme']), strtolower((string)$target['scheme']))
        || ($target['path'] ?? '') !== '/privacy-policies/'
        || isset($target['user']) || isset($target['pass'])
        || isset($target['query'], $target['fragment'])
        || headers_sent()) {
        return;
    }

    // WordPress validates that the Location target stays on the current host.
    if (wp_safe_redirect($destination, 301, 'BYL V3 Privacy Alias')) {
        exit;
    }
}
add_action('template_redirect', 'byl_v3_privacy_alias_redirect', 0);
