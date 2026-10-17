<?php
/**
 * BeYourLover Affiliate Dashboard V1.
 * Read-only AffiliateWP presentation. No new identity, commission, payout,
 * attribution, coupon or transaction engine. Uses the real Dev WP Page
 * with post_name my-affiliate (no virtual 404 override).
 */
declare(strict_types=1);
defined('ABSPATH') || exit;
require_once __DIR__.'/affiliate-remote-bridge.php';

function byl_v3_affiliate_route(): bool {
    if (is_admin() || (function_exists('wp_doing_ajax') && wp_doing_ajax())) { return false; }
    if (!in_array(strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')), ['GET', 'HEAD'], true)) { return false; }
    // Native WordPress Page ID 39938 in Dev is the route authority.
    // Use the slug rather than a hard-coded ID so future cutover can map it.
    return function_exists('is_page') && is_page('my-affiliate');
}
add_filter('template_include', static function (string $template): string {
    return byl_v3_affiliate_route() ? get_stylesheet_directory().'/template-affiliate-dashboard.php' : $template;
}, 30);
add_filter('body_class', static function (array $classes): array {
    if (byl_v3_affiliate_route()) { $classes[] = 'byl-affiliate-route'; }
    return $classes;
});
add_filter('wp_robots', static function (array $robots): array {
    if (byl_v3_affiliate_route()) { $robots['noindex'] = true; $robots['nofollow'] = true; }
    return $robots;
});
add_action('template_redirect', static function (): void {
    if (byl_v3_affiliate_route()) { nocache_headers(); }
}, 1);
add_action('wp_enqueue_scripts', static function (): void {
    if (!byl_v3_affiliate_route()) { return; }
    $relative = '/assets/css/affiliate-dashboard.css';
    $path = get_stylesheet_directory().$relative;
    wp_enqueue_style('byl-affiliate-dashboard', get_stylesheet_directory_uri().$relative, ['byl-v3-motion'], is_file($path) ? (string) filemtime($path) : '1');
    $js = '/assets/js/affiliate-dashboard.js';
    $script = get_stylesheet_directory().$js;
    wp_enqueue_script('byl-affiliate-dashboard', get_stylesheet_directory_uri().$js, [], is_file($script) ? (string) filemtime($script) : '1', true);
    wp_script_add_data('byl-affiliate-dashboard', 'strategy', 'defer');
}, 30);
/** Active creators signing in through WooCommerce are sent to their own workspace. */
add_filter('woocommerce_login_redirect', static function ($redirect, $user) {
    if (!$user instanceof WP_User || user_can($user, 'manage_options')) { return $redirect; }
    if (function_exists('affwp_get_affiliate_id')) {
        $id = (int) affwp_get_affiliate_id((int) $user->ID);
        if ($id > 0 && function_exists('affwp_get_affiliate_status') && affwp_get_affiliate_status($id) === 'active') {
            return home_url('/my-affiliate/');
        }
    }
    // Remote-bound creators may land here only after trusted binding proof.
    return defined('BYL_CREATOR_REMOTE_ENABLED') && BYL_CREATOR_REMOTE_ENABLED === true &&
        byl_v3_creator_verified_binding($user) ? home_url('/my-affiliate/') : $redirect;
}, 20, 2);
/**
 * Separate bounded read-only page cursors for historical records. Never take
 * an affiliate ID from the URL: the authenticated AffiliateWP identity wins.
 */
function byl_v3_affiliate_page_number(string $key): int {
    $raw = $_GET[$key] ?? '1';
    if (!is_string($raw) && !is_int($raw)) { return 1; }
    $value = (string) $raw;
    if (!preg_match('/^[0-9]{1,5}$/D', $value)) { return 1; }
    return min(1000, max(1, (int) $value));
}

/** Read only. Never accept a viewer-selected affiliate ID. */
function byl_v3_affiliate_snapshot(): array {
    $user = wp_get_current_user();
    $view = [
        'state' => 'guest', 'user' => $user, 'id' => 0, 'account' => null,
        'referrals' => [], 'payouts' => [], 'creatives' => [], 'referral_url' => '',
        'referral_page' => 1, 'payout_page' => 1,
        'more_referrals' => false, 'more_payouts' => false,
        'currency' => function_exists('affwp_get_currency') ? (string) affwp_get_currency() : '',
    ];
    if (!$user instanceof WP_User || !$user->exists()) { return $view; }
    $view['state'] = 'unavailable';
    // Once a verified WWW bridge is explicitly enabled it is the ONLY data
    // source. Never silently fall back to Dev's own sandbox commissions.
    if (defined('BYL_CREATOR_REMOTE_ENABLED') && BYL_CREATOR_REMOTE_ENABLED === true) {
        return byl_v3_creator_remote_snapshot($user) ?? $view;
    }
    if (!function_exists('affwp_get_affiliate_id') || !function_exists('affwp_get_affiliate_status') || !function_exists('affiliate_wp')) {
        return $view;
    }
    $id = (int) affwp_get_affiliate_id((int) $user->ID);
    if ($id <= 0 || affwp_get_affiliate_status($id) !== 'active') { return $view; }
    $engine = affiliate_wp();
    if (!is_object($engine) || !isset($engine->affiliates) || !is_object($engine->affiliates)) {
        return $view;
    }
    // AffiliateWP 2.35+ exposes affwp_get_affiliate(); the DB instance does
    // not guarantee get_affiliate(). Keep older mock/plugin compatibility.
    $account = function_exists('affwp_get_affiliate')
        ? affwp_get_affiliate($id)
        : (method_exists($engine->affiliates, 'get_affiliate') ? $engine->affiliates->get_affiliate($id) : null);
    if (!is_object($account) || (isset($account->user_id) && (int) $account->user_id !== (int) $user->ID)) { return $view; }
    $view['state'] = 'active';
    $view['id'] = $id;
    $view['account'] = $account;
    if (function_exists('affwp_get_affiliate_referral_url')) {
        $url = affwp_get_affiliate_referral_url(['affiliate_id' => $id, 'base_url' => home_url('/')]);
        if (is_string($url) && wp_http_validate_url($url) && wp_parse_url($url, PHP_URL_HOST) === wp_parse_url(home_url('/'), PHP_URL_HOST)) {
            $view['referral_url'] = $url;
        }
    }
    $view['referral_page'] = byl_v3_affiliate_page_number('byl_ref_page');
    $view['payout_page'] = byl_v3_affiliate_page_number('byl_pay_page');
    if (isset($engine->referrals) && is_object($engine->referrals) && method_exists($engine->referrals, 'get_referrals')) {
        $limit = 12;
        $items = $engine->referrals->get_referrals([
            'affiliate_id' => $id, 'number' => $limit + 1,
            'offset' => ($view['referral_page'] - 1) * $limit,
            'orderby' => 'date', 'order' => 'DESC',
        ]);
        if (is_array($items)) {
            $owned = array_values(array_filter($items, static function ($entry) use ($id): bool {
                return is_object($entry) && isset($entry->affiliate_id) && (int) $entry->affiliate_id === $id;
            }));
            $view['more_referrals'] = count($owned) > $limit;
            $view['referrals'] = array_slice($owned, 0, $limit);
        }
    }
    if (isset($engine->payouts) && is_object($engine->payouts) && method_exists($engine->payouts, 'get_payouts')) {
        $limit = 4;
        $items = $engine->payouts->get_payouts([
            'affiliate_id' => $id, 'number' => $limit + 1,
            'offset' => ($view['payout_page'] - 1) * $limit,
            'orderby' => 'date', 'order' => 'DESC',
        ]);
        if (is_array($items)) {
            $owned = array_values(array_filter($items, static function ($entry) use ($id): bool {
                return is_object($entry) && isset($entry->affiliate_id) && (int) $entry->affiliate_id === $id;
            }));
            $view['more_payouts'] = count($owned) > $limit;
            $view['payouts'] = array_slice($owned, 0, $limit);
        }
    }
    if (isset($engine->creatives) && is_object($engine->creatives) && method_exists($engine->creatives, 'get_creatives')) {
        $items = $engine->creatives->get_creatives(['number' => 4, 'status' => 'active']);
        if (is_array($items)) { $view['creatives'] = array_values(array_filter($items, 'is_object')); }
    }
    return $view;
}
function byl_v3_affiliate_money($amount, string $currency): string {
    if (!is_numeric($amount)) { return '—'; }
    return strtoupper($currency ?: 'USD').' '.number_format_i18n((float) $amount, 2);
}
function byl_v3_affiliate_metric($value, bool $money = false, string $currency = ''): string {
    if (!is_numeric($value)) { return '—'; }
    return $money ? byl_v3_affiliate_money($value, $currency) : number_format_i18n((int) $value);
}
