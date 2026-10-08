# S0-S2 Checkout / Shipping Promotion Contract

Status: S1 policy contract + S2 admin UI only. This worktree is local-only; no Dev or Production deployment is authorized by this phase.

## Current-state audit

- BYL Platform is currently a minimal first-party plugin containing the bootstrap, migration runner, and CLI.
- The active local theme is `beyourlover-v3`; checkout/shipping policy belongs in the platform plugin, not the theme.
- WooCommerce coupons are enabled, but `BYL49` does not currently exist.
- Two smart-coupon plugins remain active. This phase does not depend on either plugin.
- Current local WooCommerce free-shipping thresholds are preserved as the effective initial zone overrides: HK (`zone_id` 1) = `0`; zone 1 (`zone_id` 5) = `69`; zone 2 (`zone_id` 4) = `129`; War Zone (`zone_id` 3) = `169`; Rest of world (`zone_id` 0) = `69`.
- Runtime compatibility fact: on the current local WooCommerce runtime, `WC_Shipping_Free_Shipping` exposes public `id = 'free_shipping'` but does not provide `get_method_id()` or `method_id`. The policy therefore resolves the method ID via `get_method_id()` when available, then public `id`, then `method_id`, and only inspects `min_amount` after identifying `free_shipping`.
- Rest of world currently has WooCommerce `requires = either`. S3 must preserve and account for that existing availability semantics without overwriting WooCommerce settings.

## S1 policy contract

The first-party option is `byl_shipping_promotion_settings`, schema version `1`, with these defaults:

- `mode`: `automatic`
- `free_shipping_code`: `BYL49`
- `global_threshold`: `49.00`
- `eligibility_basis`: `pre_discount_merchandise_subtotal` (fixed/read-only in the UI)
- `zone_overrides`: keyed by WooCommerce zone ID, each selecting `global` or `override` and carrying a threshold.

When the BYL option is absent, the policy derives existing WooCommerce free-shipping `min_amount` values and initializes those zones as `override`. Zones without an existing free-shipping threshold use the global threshold. Rest of world (`zone_id` `0`) is always surfaced. S3 must account for existing WooCommerce availability semantics, including Rest of world `requires = either`, without overwriting those settings.

The lightweight policy helpers expose mode, code, global threshold, eligibility basis, per-zone config, and effective zone threshold for later S3-S6 work. They are independent of frontend DOM or rendering.

## S2 admin boundary

The UI is available at `BYL Platform -> Checkout / Shipping`, protected by `manage_woocommerce` and the WordPress Settings API. It provides automatic versus require-code mode, the code field, global threshold, and per-zone global/override selection.

Require-code mode displays the explicit single promo-code-slot warning. It does not create a second code slot and does not enforce coupons in this phase.

This phase never writes or overwrites WooCommerce free-shipping method settings. It does not add checkout/cart frontend rendering, create coupons, modify payment configuration, modify real orders, or alter WooCommerce shipping methods.

## Legacy S4 migration warning

The old inactive Shoptimizer child logic `one_applied_coupon_only` excludes codes containing `free`, so it does not satisfy the frozen one-slot-total contract and must not be ported as-is. The existing legacy `summersale` logic also directly mutates `applied_coupons`; future S4 work must replace it with first-party safe enforcement rather than copy either legacy path.
