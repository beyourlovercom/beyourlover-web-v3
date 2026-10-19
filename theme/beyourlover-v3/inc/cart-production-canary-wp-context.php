<?php
/**
 * B4 offline prototype: WordPress-attested Cart Canary context, NOT BOOTSTRAPPED.
 *
 * This file contains NO add_action/add_filter, no enqueue, no template switch,
 * no Woo order, payment, email or database write and NO production installation.
 * It must be explicitly loaded by a separately approved adapter in a verified
 * deployment. A PHP server policy is an approval record, NOT proof of runtime
 * availability; every physical / guest / CDN / rollback test is still required.
 */
declare(strict_types=1);
defined('ABSPATH') || exit;

/**
 * Verify physical PHP component bytes against the locked release receipt.
 * A config release SHA alone is not proof that these installed files match.
 * Exact filenames only: no arbitrary paths or contents from remote inputs.
 */
function byl_v3_cart_prod_canary_component_files_verified($expected): bool {
    $allowlist = [
        'cart-production-canary-gates.php',
        'cart-production-canary-compatibility.php',
        'cart-production-canary-wp-context.php',
    ];
    if (!is_array($expected) || count($expected) !== count($allowlist)) {
        return false;
    }
    foreach ($allowlist as $name) {
        $digest = $expected[$name] ?? null;
        if (!is_string($digest)
            || !preg_match('/\\A[a-f0-9]{64}\\z/D', $digest)) {
            return false;
        }
        $file = __DIR__.'/'.$name;
        if (!is_file($file) || !is_readable($file)) {
            return false;
        }
        $actual = hash_file('sha256', $file);
        if (!is_string($actual) || !hash_equals($digest, $actual)) {
            return false;
        }
    }
    return true;
}

/**
 * Derive trusted authentication and active-theme fields from WP only.
 *
 * Operator policy must be a *server-defined PHP constant* provisioned only
 * through a separately authorized deployment; public URL/cookie/options and
 * browser JS cannot set eligibility. No code here provisions that constant.
 *
 * @return array<string,mixed> Empty => fail-closed.
 */
function byl_v3_cart_prod_canary_wp_context(): array {
    if (!defined('BYL_CART_CANARY_V1_SERVER_POLICY')) {
        return [];
    }
    $policy = constant('BYL_CART_CANARY_V1_SERVER_POLICY');
    if (!is_array($policy)) {
        return [];
    }
    // Require physical file hashes from the signed/reviewed deployment receipt.
    // No receipt means OFF, even when a human copies both release SHA strings.
    if (!byl_v3_cart_prod_canary_component_files_verified(
        $policy['approved_component_sha256'] ?? null
    )) {
        return [];
    }
    foreach ([
        'home_url', 'wp_parse_url', 'wp_get_environment_type',
        'is_user_logged_in', 'get_current_user_id', 'current_user_can',
        'get_stylesheet_directory', 'wp_get_theme', 'is_admin', 'is_checkout',
    ] as $required) {
        if (!function_exists($required)) {
            return [];
        }
    }
    if (is_admin() || is_checkout()) {
        return [];
    }

    $theme = wp_get_theme();
    if (!is_object($theme) || !method_exists($theme, 'get_stylesheet')
        || !method_exists($theme, 'get_template')) {
        return [];
    }
    $actualStyle = (string) $theme->get_stylesheet();
    $actualParent = (string) $theme->get_template();
    $approvedStyle = $policy['approved_stylesheet'] ?? null;
    $approvedParent = $policy['approved_parent_template'] ?? null;
    if (!is_string($approvedStyle) || !is_string($approvedParent)
        || $approvedStyle === '' || $approvedParent === ''
        || !hash_equals($approvedStyle, $actualStyle)
        || !hash_equals($approvedParent, $actualParent)) {
        return [];
    }

    // The on-disk SHA256 is read *from the running active child theme*.
    // In particular it will change when Window A edits functions.php.
    // Do not import an old Dev/legacy backup as a substitute for this hash.
    $themeRoot = get_stylesheet_directory();
    if (!is_string($themeRoot) || $themeRoot === '') {
        return [];
    }
    $themeEntry = rtrim($themeRoot, '/\\') . '/functions.php';
    if (!is_file($themeEntry) || !is_readable($themeEntry)) {
        return [];
    }
    $fingerprint = hash_file('sha256', $themeEntry);
    if (!is_string($fingerprint) || !preg_match('/\\A[a-f0-9]{64}\\z/D', $fingerprint)) {
        return [];
    }
    $approvedFingerprint = $policy['approved_theme_fingerprint'] ?? null;
    if (!is_string($approvedFingerprint)
        || !preg_match('/\\A[a-f0-9]{64}\\z/D', $approvedFingerprint)
        || !hash_equals($approvedFingerprint, $fingerprint)) {
        return [];
    }

    $id = get_current_user_id();
    $loggedIn = is_user_logged_in();
    $staffIds = $policy['allowed_staff_ids'] ?? null;
    if ($loggedIn !== true || !is_int($id) || $id <= 0
        || !is_array($staffIds) || $staffIds === []
        || !in_array($id, $staffIds, true)
        || current_user_can('manage_woocommerce') !== true) {
        return [];
    }
    // A future runtime adapter must bypass *all* full-page caches before
    // producing any employee-specific HTML. Merely trusting a config value
    // is insufficient. This check does not set headers or alter WP behavior.
    if (!defined('DONOTCACHEPAGE') || constant('DONOTCACHEPAGE') !== true) {
        return [];
    }

    $host = wp_parse_url(home_url('/'), PHP_URL_HOST);
    if ($host !== 'www.beyourlover.com'
        || wp_get_environment_type() !== 'production') {
        return [];
    }

    // Only an explicit allowlist of policy fields may be copied. In
    // particular host, user_id, user capability, on-disk fingerprint and
    // WP environment can NEVER be overridden by a supplied policy array.
    $bools = [
        'master_enabled', 'incident_p0_cleared', 'commerce_e2e_cleared',
        'active_theme_verified', 'legacy_fallback_verified',
        'cache_isolation_verified', 'runtime_hash_verified',
        'drawer_enabled', 'page_enabled',
        'legacy_cart_fragments_recovered',
        'legacy_cold_guest_empty_cart_pass',
        'legacy_cold_guest_filled_cart_pass',
        'legacy_fragment_latency_under_timeout_pass',
        'www_dynamic_ajax_503_stable',
        'woo_post_upgrade_integrity_verified',
        'payment_gateway_readonly_health_verified',
        'legacy_drawer_verified', 'legacy_cart_page_verified',
        'www_post_hotfix_hashes_reconciled',
        'legacy_restore_artifact_verified',
        'www_theme_identity_attested', 'www_integration_point_attested',
        'guest_after_staff_cache_test_pass', 'woocommerce_cart_session_isolated',
        'checkout_route_untouched', 'staff_html_uncacheable',
        'cloudflare_guest_segregated', 'hostinger_guest_segregated',
        'woo_fragments_legacy_preserved',
        'drawer_adapter_attested', 'drawer_trigger_no_double_bind',
        'drawer_woo_store_api_contract_pass', 'drawer_phase_owner_pass',
        'page_template_route_attested',
        'page_native_woo_cart_authority_attested',
        'page_legacy_fallback_attested',
        'page_empty_state_independently_gated',
    ];
    $context = [];
    foreach ($bools as $field) {
        $context[$field] = ($policy[$field] ?? null) === true;
    }
    // Require the policy's cache proof and the actual no-cache server flag.
    $context['staff_html_uncacheable'] = $context['staff_html_uncacheable']
        && constant('DONOTCACHEPAGE') === true;
    $context['host'] = $host;
    $context['wp_environment'] = wp_get_environment_type();
    $context['user_id'] = $id;
    $context['is_logged_in'] = $loggedIn;
    $context['can_manage_woocommerce'] = true;
    $context['allowed_staff_ids'] = $staffIds;
    $context['live_theme_fingerprint'] = $fingerprint;
    $context['approved_theme_fingerprint'] = $approvedFingerprint;
    $context['approved_release_sha'] = $policy['approved_release_sha'] ?? null;
    $context['installed_release_sha'] = $policy['installed_release_sha'] ?? null;
    return $context;
}

/**
 * Evidence-only decision. Returns both legacy when WordPress runtime isn't
 * attested, when the current P0 isn't closed, or when both surfaces are enabled.
 * No hooks/templates are registered or modified by this function.
 */
function byl_v3_cart_prod_canary_wp_decision(): array {
    $legacy = ['drawer' => 'legacy', 'page' => 'legacy', 'reason' => 'wp_adapter_not_ready'];
    if (!function_exists('byl_v3_cart_prod_canary_compatibility_decision')) {
        return $legacy;
    }
    $context = byl_v3_cart_prod_canary_wp_context();
    return $context ? byl_v3_cart_prod_canary_compatibility_decision($context) : $legacy;
}
