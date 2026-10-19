<?php
/**
 * Cart Production Canary V1 — pure, NOT wired-to-runtime eligibility checker.
 *
 * This file is intentionally NOT required by theme bootstrap, cart hooks or
 * template routing. Shipping code or flipping production settings is not
 * authorized by the existence of this implementation.
 *
 * The eventual adapter must supply ALL context from trusted server-side WP
 * auth, reviewed deployment fingerprint, incident/E2E signoffs and cache gates.
 * Never populate this array from GET/POST/cookies, a browser script or claims.
 *
 * No DB writes, no Woo session mutations, no WP hooks, no side effects.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

/**
 * Returns true ONLY for an explicitly allowlisted, authenticated, authorized
 * staff user on the exact WWW production host and approved, cache-isolated
 * release. Unknown surfaces, missing fields and non-boolean truthy strings
 * fail closed. The independent "drawer" and "page" switches are separate.
 *
 * @param string $surface 'drawer' or 'page' (no other surface accepted).
 * @param array<string,mixed> $context trusted server-generated fields only.
 */
function byl_v3_cart_prod_canary_eligible(string $surface, array $context): bool {
    if ($surface !== 'drawer' && $surface !== 'page') {
        return false;
    }

    if (($context['host'] ?? null) !== 'www.beyourlover.com'
        || ($context['wp_environment'] ?? null) !== 'production') {
        return false;
    }

    // Every approval MUST be explicit, current, server-attested boolean true.
    // These booleans are evidence gates, NOT automatic observations here.
    foreach ([
        'master_enabled',
        'incident_p0_cleared',
        'commerce_e2e_cleared',
        'active_theme_verified',
        'legacy_fallback_verified',
        'cache_isolation_verified',
        'runtime_hash_verified',
        'is_logged_in',
        'can_manage_woocommerce',
    ] as $required) {
        if (($context[$required] ?? null) !== true) {
            return false;
        }
    }

    // Drawer and /cart page are independently opt-in; no common side effect.
    if (($context[$surface.'_enabled'] ?? null) !== true) {
        return false;
    }

    $id = $context['user_id'] ?? null;
    $staff = $context['allowed_staff_ids'] ?? null;
    if (!is_int($id) || $id <= 0 || !is_array($staff) || $staff === []) {
        return false;
    }
    if (!in_array($id, $staff, true)) {
        return false;
    }

    // A full exact 40-char Git commit must be explicitly approved and match
    // the actually installed canary package fingerprint as attested by the
    // future deployment adapter. Never read this approval from user input.
    $approved = $context['approved_release_sha'] ?? null;
    $installed = $context['installed_release_sha'] ?? null;
    if (!is_string($approved) || !is_string($installed)
        || !preg_match('/\\A[a-f0-9]{40}\\z/D', $approved)
        || !hash_equals($approved, $installed)) {
        return false;
    }

    return true;
}
