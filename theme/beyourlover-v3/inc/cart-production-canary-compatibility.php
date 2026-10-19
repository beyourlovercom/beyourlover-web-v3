<?php
/**
 * Cart Production Canary V1 B3: Shoptimizer-to-BYL compatibility decision.
 *
 * Pure and NOT WIRED to any theme, plugin or WP lifecycle. Never include this
 * file from a production bootstrap merely because it is present in a ZIP.
 * Deployment/route adaptation awaits verified WWW extension point, theme
 * fingerprints, A's recovered legacy cart, and a distinct Owner approval.
 *
 * This decision does not enqueue scripts, select templates, query databases,
 * update the Woo session, or generate any customer-specific HTML.
 */
declare(strict_types=1);
defined('ABSPATH') || exit;

/**
 * Fail closed to the working legacy public cart unless the independent B3
 * adapter and actual running legacy theme were separately attested.
 *
 * The $evidence is supplied ONLY by a future trusted server-side adapter,
 * never GET/POST/cookie/localStorage or Javascript. Checked flags do not
 * prove the checks happened: a future adapter must actually implement them.
 *
 * @return array{drawer: string, page: string, reason: string}
 */
function byl_v3_cart_prod_canary_compatibility_decision(array $evidence): array {
    $legacy = ['drawer' => 'legacy', 'page' => 'legacy'];
    $deny = static function (string $reason) use ($legacy): array {
        return $legacy + ['reason' => $reason];
    };

    if (!function_exists('byl_v3_cart_prod_canary_eligible')) {
        return $deny('eligibility_helper_absent');
    }

    // Hard stop while the other window's P0 Shoptimizer fragments repair is
    // incomplete or its changed files/config and rollback are not reconciled.
    foreach ([
        'incident_p0_cleared',
        'legacy_cart_fragments_recovered',
        'legacy_drawer_verified',
        'legacy_cart_page_verified',
        'www_post_hotfix_hashes_reconciled',
        'legacy_restore_artifact_verified',
        'www_theme_identity_attested',
        'www_integration_point_attested',
        'guest_after_staff_cache_test_pass',
        'woocommerce_cart_session_isolated',
        'checkout_route_untouched',
    ] as $evidenceGate) {
        if (($evidence[$evidenceGate] ?? null) !== true) {
            return $deny('prerequisite_'.$evidenceGate);
        }
    }

    // Do not infer the active theme identity from the legacy localdev audit.
    // Require a fresh exact production runtime match, not a guessed name.
    $runningTheme = $evidence['live_theme_fingerprint'] ?? null;
    $approvedTheme = $evidence['approved_theme_fingerprint'] ?? null;
    if (!is_string($runningTheme) || !is_string($approvedTheme)
        || !preg_match('/\\A[a-f0-9]{64}\\z/D', $approvedTheme)
        || !hash_equals($approvedTheme, $runningTheme)) {
        return $deny('theme_fingerprint_mismatch');
    }

    // An infrastructure cache-isolation statement is NOT sufficient alone.
    // A real guest-after-staff negative UAT and per-request cache bypass need
    // confirmation before a staff-specific Drawer/Page is ever rendered.
    foreach (['staff_html_uncacheable', 'cloudflare_guest_segregated',
              'hostinger_guest_segregated', 'woo_fragments_legacy_preserved'] as $gate) {
        if (($evidence[$gate] ?? null) !== true) {
            return $deny('cache_or_woo_'.$gate);
        }
    }

    $wantDrawer = ($evidence['drawer_enabled'] ?? null) === true;
    $wantPage = ($evidence['page_enabled'] ?? null) === true;

    // P1 sequencing is Drawer -> stabilization -> separate /cart page. If a
    // rollout accidentally sets both switches on, render NEITHER new UI.
    // A later owner-approved P2 can replace this deliberate conservative gate.
    if ($wantDrawer && $wantPage) {
        return $deny('simultaneous_cart_surface_activation_blocked');
    }
    if (!$wantDrawer && !$wantPage) {
        return $deny('all_surfaces_off');
    }

    if ($wantDrawer) {
        foreach (['drawer_adapter_attested', 'drawer_trigger_no_double_bind',
                  'drawer_woo_store_api_contract_pass'] as $gate) {
            if (($evidence[$gate] ?? null) !== true) {
                return $deny('drawer_'.$gate);
            }
        }
        if (byl_v3_cart_prod_canary_eligible('drawer', $evidence)) {
            return ['drawer' => 'canary', 'page' => 'legacy', 'reason' => 'staff_drawer_only'];
        }
        return $deny('drawer_eligibility_denied');
    }

    // The new /cart page has different Woo shortcode and template entry
    // semantics than the legacy Cart Block. Never switch merely because
    // page-cart.php exists in an inactive standalone V3 theme.
    foreach (['drawer_phase_owner_pass', 'page_template_route_attested',
              'page_native_woo_cart_authority_attested',
              'page_legacy_fallback_attested', 'page_empty_state_independently_gated']
              as $gate) {
        if (($evidence[$gate] ?? null) !== true) {
            return $deny('page_'.$gate);
        }
    }

    if (byl_v3_cart_prod_canary_eligible('page', $evidence)) {
        return ['drawer' => 'legacy', 'page' => 'canary', 'reason' => 'staff_page_only'];
    }
    return $deny('page_eligibility_denied');
}
