# LELO Checkout L3 — source-only three-step implementation gate
Date: 2026-10-15 | linked Issue #89 / Docs Draft PR #118 / E2E #87

## Completed in isolated feature branch, not deployed
- Replaces 4 customer-facing Classic Checkout stages with exactly **Shipping + Payment**, preceded by existing Cart Drawer as step 1 (Cart → Shipping → Payment).
- Moves Woo shipping-rate selection UI into Shipping, prior to address inputs; visible rates and monetary amounts come from native Woo shipping_method inputs, not mocked Express or free rates. Address changes must recalculate actual Woo rates during real Dev UAT.
- Preserves Woo classic checkout shortcode, native payment container and gateway initialization, nonce/server checks, place_order delegation, checkout error events, anti-repeat UI locking, Terms and legal links. Woo remains financial authority.
- Adds guest account/login link and authenticated Edit Profile link via existing Woo routes; does NOT implement automatic customer creation, email delivery or marketing subscription.
- Reorders required phone before ZIP with full-width delivery privacy copy, expands optional apt/organization fields only on click while preserving prefilled values.
- Adds a hidden Country Notices presentation slot; no tax IDs, country-specific rules, or notices are fabricated. They remain Andy Core configuration + separate country/logistics validation.
- Removes Dev-only Terms/review invitation auto-prechecked experiment, without bypassing Production Woo server legal validation.
- Checkout remains on secure /checkout/ fallback route. Full overlay Checkout Drawer mounted on listing/product pages is NOT implemented in this slice; needs separate single form/session and gateway/3DS compatibility gate.
- Gateway logos in visual mocks illustrative; only eligible Woo gateways may display.

## Verified static/isolated tests (no order, DB writes or email)
- PHP lint form and checkout fields; JS syntax; Theme OS validate.
- Existing Checkout progressive UX source contract updated for exactly two Checkout phases with Cart as predecessor and no auto pre-consent.
- New test_checkout_lelo_three_step_l3_contract.py asserts shipping rate placement, native handoff, account link, optional fields, hidden notices and absence of forbidden business writes.
- Existing checkout security shell, cart/checkout handoff, Dev legal handoff and shipping/promotion documentation baseline.

## Holds before L3 Dev UAT
1. Read-only Woo settings required: gateway eligibility, country rate IDs/ETAs, guest creation, Terms plugins, regional tax, shipping/billing setup. Anonymous endpoints returned 401/403 or empty cart.
2. Full Drawer modal architecture and secure fallback to /checkout/ must pass existing isolated Dev cart session; route-level phase is the first implementation.
3. Source tests DO NOT validate live 3DS, Woo updated_checkout fragments, gateway JS, tax or final totals.
4. Country Notices, Tax ID and auto-account/Mail OS are separate implementation branches with approval gates.
5. GitHub Actions Foundation CI billing restriction may stop runner; local tests DO NOT count as hosted CI PASS.

No main merge, Dev/WWW deploy, cart/order/payment/customer/DB/mail writes or READY device restart.
