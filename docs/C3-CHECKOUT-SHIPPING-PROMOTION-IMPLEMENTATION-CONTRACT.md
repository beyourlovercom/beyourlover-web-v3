# C3 Checkout / Shipping Promotion Implementation Contract

Status: implementation contract for the C2 checkout and shipping-promotion flow. Documentation only; this file does not authorize runtime, CI, deployment, or Production changes.

## Scope and rollout gates

Code deploy and configuration activation are separate gates. There is no second admin/master switch.

The persisted policy option is the activation configuration write:

```text
has_persisted_settings() = option exists + valid schema
is_runtime_active() = has_persisted_settings()
```

Derived S2 defaults do not activate the runtime. Production activation occurs only when the Owner separately authorizes the Production settings write after the code deploy. Production is unauthorized until both gates are separately approved; this document does not authorize a Production write.

The policy option must contain a valid, versioned schema for the configured threshold, mode, policy code, and free-shipping behavior. Invalid or absent settings fail closed. No fallback, derived default, or second switch may make the runtime active.

## Eligibility basis

Eligibility MUST use the numeric value returned by `WC()->cart->get_displayed_subtotal()` before WooCommerce subtracts `discount_total` or `discount_tax`.

The displayed subtotal preserves WooCommerce's configured displayed tax basis: if the cart display is tax-inclusive, eligibility subtotal is tax-inclusive; if the cart display is tax-exclusive, eligibility subtotal is tax-exclusive. Tax is not always excluded.

Eligibility excludes shipping and fees and does not subtract promotional or AffiliateWP discounts. Round the numeric eligibility value to `wc_get_price_decimals()` before comparing it with the configured threshold. The comparison must therefore use the cart's displayed merchandise subtotal, not an order subtotal or a post-discount total.

## BYL49 policy-code recognition

While `is_runtime_active()` is true, use the verified WooCommerce hook `woocommerce_get_shop_coupon_data` to provide the configured policy code as a virtual zero-discount coupon. Do not create a database shop coupon for `BYL49`.

Normalize the configured code with `wc_format_coupon_code`. The minimal virtual coupon data must document these fields:

```text
code: normalized policy code
discount_type: fixed_cart (or an equivalent zero-discount type)
amount: 0
virtual: true
free_shipping: false
```

`free_shipping: false` is intentional. The coupon is a virtual, zero-discount Woo coupon whose only policy purpose is to occupy the one promo-code slot and participate in normal WooCommerce applied-coupon and order coupon-line state. The coupon `free_shipping` flag does not decide policy free shipping.

## S3 Woo Shipping Adapter

While `is_runtime_active()` is true, the S3 Woo Shipping Adapter owns policy free-shipping availability through `woocommerce_shipping_free_shipping_is_available`, receiving WooCommerce's `$is_available`, `$package`, and `$shipping_method` arguments. When the runtime is inactive, the filter must return the original `$is_available` untouched.

When the runtime is active, the adapter MUST:

1. Use `WC_Shipping_Zones::get_zone_matching_package($package)` for the effective Woo zone. It must not recreate WooCommerce's geography or zone matching.
2. Read the effective zone threshold from the BYL policy.
3. Read `WC()->cart->get_displayed_subtotal()` before subtracting discounts and round it to `wc_get_price_decimals()`.
4. In Automatic mode, return available exactly when eligibility is greater than or equal to the effective threshold.
5. In Require-code mode, return available exactly when eligibility is greater than or equal to the effective threshold and the normalized configured policy code is present in `WC()->cart->get_applied_coupons()`.
6. Never write or mutate WooCommerce zone or free-shipping method settings.

This override is required because the current WooCommerce free-shipping method calculates coupon/free-shipping and minimum-amount eligibility internally, and with `ignore_discounts=no` subtracts `discount_total` and `discount_tax` before applying its final decision. The BYL contract requires the pre-discount displayed subtotal, so the adapter must own the final availability decision while the BYL runtime is active.

When the runtime gate is off, the virtual coupon recognition and BYL shipping override are inactive; there is no permanent record to delete.

This virtual-coupon approach requires local verification with smart-coupon plugins before S4 PASS, including verification that the hook, applied-coupon state, totals, and order coupon lines remain compatible.

## One-slot and replacement semantics

The server enforces one total active promo slot per order. Affiliate, normal WooCommerce coupon, and BYL49 WooCommerce coupon codes all share this one slot. AffiliateWP referral attribution that is not itself a WooCommerce coupon remains separate and must not be lost. The UI must not be treated as the enforcement boundary.

Validate the candidate code first. If replacement validation fails, leave the current valid code intact. Only after validation succeeds, remove the old coupon and apply the replacement in the same slot. A valid automatic threshold result may coexist with one entered code, but it does not create another promo slot. Preserve the C2 sequence and exact replacement copy:

`Replace your current code with BYL49 to unlock free shipping.`

AffiliateWP semantics remain unchanged: AffiliateWP attribution and discount behavior are not converted into the checkout promotion code, do not create a second slot, and are not subtracted from the eligibility basis described above.

The verified WooCommerce replacement behavior is the `woocommerce_coupon_get_individual_use` filter. While the customer cart/checkout runtime is active, BYL returns `true` for every Woo coupon, including ordinary and affiliate coupon codes. Core `WC_Cart::apply_coupon` validates the candidate before candidate individual-use handling; a valid candidate then removes the existing applied coupon before appending the new code, while an invalid candidate leaves the existing valid code intact. This uses Woo's native flow and does not alter normal wp-admin coupon editing.

Store API compatibility uses the four-argument `woocommerce_apply_with_individual_use_coupon` filter. In `Automattic\WooCommerce\StoreApi\Utilities\CartController::apply_coupon`, WooCommerce validates the candidate first, then checks existing individual-use coupons through this filter; only afterward does native Store API logic remove old coupons when the validated candidate is individual-use and apply the new code. While the BYL runtime is active in customer context, BYL returns `true` for this guard so the already-validated candidate reaches that native replacement path. Outside active customer context, BYL returns the incoming boolean unchanged. BYL does not implement custom remove-before-validate logic.

For stale sessions that load with multiple applied Woo coupons, `woocommerce_cart_loaded_from_session` deterministically retains the first applied coupon in Woo's stored order and removes each later coupon with the cart API. This is in-memory/cart-session normalization only; it does not write a coupon post or alter AffiliateWP referral attribution that is not itself a Woo coupon.

## Hook and API contract

| Stage | Hook/API | Contract |
| --- | --- | --- |
| Eligibility | `WC()->cart->get_displayed_subtotal()` | Numeric displayed merchandise subtotal before `discount_total` / `discount_tax`; preserve configured tax display basis; exclude shipping and fees. |
| Policy activation | `has_persisted_settings()` / `is_runtime_active()` | Persisted valid schema is the only activation state; derived defaults do not activate. |
| Virtual policy code (S4) | `woocommerce_get_shop_coupon_data` | While runtime is active, return the normalized configured code as a virtual zero-discount coupon; no DB shop-coupon creation. |
| Coupon state | `WC()->cart->get_applied_coupons()` | The virtual code participates in normal WooCommerce applied-coupon and order coupon-line flow. |
| Shipping availability (S3) | `woocommerce_shipping_free_shipping_is_available` | While runtime is active, use Woo's matching zone and BYL's pre-discount threshold/code policy; while inactive, return `$is_available` unchanged. |
| Matching zone | `WC_Shipping_Zones::get_zone_matching_package($package)` | Use WooCommerce's own package geography and zone selection. |
| Price precision | `wc_get_price_decimals()` | Round the displayed eligibility subtotal before threshold comparison. |
| One-slot enforcement | `woocommerce_coupon_get_individual_use` | Customer cart/checkout only; force every Woo coupon to individual-use so core WooCommerce validates first and natively replaces the prior coupon. |
| Store API replacement compatibility | `woocommerce_apply_with_individual_use_coupon` (4 args) | Customer cart/checkout only; after Store API validates the candidate, return `true` for its existing-individual-use guard so native individual-use replacement removes old coupons before applying the candidate; otherwise preserve the incoming boolean. |
| Stale-session normalization | `woocommerce_cart_loaded_from_session` | Customer cart/checkout only; retain the first applied coupon and remove later coupons deterministically, without database coupon creation or Woo zone/settings writes. |

## S4 verification and authorization

S4 PASS requires local smart-coupon compatibility verification for `woocommerce_get_shop_coupon_data`, the virtual zero-discount fields, applied-coupon state, free-shipping decision, and order coupon-line behavior.

Production is unauthorized. No Production settings write, deployment, activation, or live-data change is authorized by this contract.
