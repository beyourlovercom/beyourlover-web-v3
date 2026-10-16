# Checkout Payment Gateway Availability P0 — independent B diagnostic

Date: 2026-10-16 · Owner Issue #121 · `beyourlovercom/beyourlover-web-v3`

## Status: SOURCE ONLY — not installed and not deployed

This is a **single optional MU-plugin source file** for a future narrowly approved, Dev-only diagnostic Canary:

- `byl-payment-gateway-readonly.php`
- Does **not autoload** from the website theme, is **not** in the WordPress plugin deployment path, and is not intended for merging to `main` without explicit approval.
- It does not modify `inc/checkout.php`, `form-checkout.php`, `checkout.js`, `checkout-drawer.js`, any CSS, or any files owned by parallel A: Checkout Visual Polish V2.

## Evidence justifying the diagnostic

1. Existing populated Dev cart: 2 items, USD 185.00, requires payment, billing/shipping country codes CN/CN.
2. Woo native `/checkout/` and `?byl_checkout_drawer=1`: each shows **zero native standard gateway IDs** and Woo's genuine *no available methods* notice.
3. Authorized Woo REST GET: `woocommerce_payments` registered and enabled; independent `stripe` disabled.
4. Authorized WooPayments REST GET: account **complete**, `payments_enabled=true`, `details_submitted=true`, `card_payments=active`, no open requirements. WooPayments Test/Dev enabled. Enabled methods and available-method IDs include `card`, country US, USD.
5. Consequently **gateway enabled** differs from **actual checkout availability**, but the precise PHP predicate or filtering callback is unproven. Wallet buttons do not prove transaction readiness.

No merchant IDs, authentication tokens, personally identifying cart details, complete REST payloads, or account contact information are preserved.

## Hard safety gates

The file returns without acting unless **all** conditions hold: Dev host `dev.beyourlover.com`, WordPress environment `staging`, native checkout route, existing session, GET method, logged-in `manage_woocommerce` administrator, not Order Pay / Thank You / WP admin. The file hooks the native Woo gateway list **by identity** at early/late filter priorities and emits *only* a JSON data island in that administrator's checkout document.

It neither enables gateways nor executes payment/order/cart POSTs. It contains no REST handler, AJAX handler, form action, email, persistent writes, payment credentials, logging, or forced pay-button activation.

## What the JSON means

Read `document.querySelector('#byl-p0-payment-audit')?.textContent` **only after separately approved Dev installation**.

- `registered`: configured gateway IDs and whether each PHP gateway's `enabled` property is `yes`.
- `before_other_filters`: gateway IDs **after Woo's own `is_available()` screening** but at the beginning of the WordPress `woocommerce_available_payment_gateways` filter.
- `after_other_filters`: gateway IDs after registered filter callbacks have run.
- `callback_names`: non-sensitive callback class/method names and priorities for the Woo availability filter, not closure values or file paths.
- `needs_payment`, `currency_code`, `billing_country_code`: safe scalar context, no amounts or personal address.

**Interpretation:** if card is enabled in `registered` but already absent in `before_other_filters`, the problem is **not proven** to be a downstream availability filter; investigate PHP `is_available()`/gateway construction. If it is present before but absent after, inspect the responsible filter. If both include card but the page is empty, inspect the Woo template/fragment integration. Call-sample traces are capped at eight invocations.

## Mandatory separate Dev-only approval

Before **any** installation: exact head SHA and blob SHA, explicit allowed destination, verified Dev identity, old-file/absence baseline, backup, rollback, static lint, and explicit Owner approval. Do not reuse PR #120 Canary authorization.

Potential deployment **only after Owner approval**: place this file under the Dev site's `wp-content/mu-plugins/` directory. Do **not** upload to WWW or modify existing plugins.

Rollback **only with matching fresh Owner scope**: remove that one Dev MU-plugin file after capturing the JSON results. Verify it is absent and Checkout unchanged.

## Safe UAT

1. Use the pre-existing, owner-authorized Dev cart; **do not add cart items**.
2. Compare `/checkout/` and `?byl_checkout_drawer=1` without submitting either form.
3. Capture only the diagnostic JSON's gateway IDs, booleans, and callback names; do not upload cookies, nonce, request headers, contact/address, account IDs, client secrets or raw network bodies.
4. Preserve the native **disabled** PAY NOW when Woo has no genuine available gateway.
5. After root cause is proven, create a separate minimal fix plan/PR. No live/test toggle, payment or order attempts, Woo/Stripe settings writes, database/mail writes, merge, or Dev/WWW deploy without separate authorization.

## Local verification

- PHP CLI syntax check of exact GitHub-committed source: **PASS** (`No syntax errors detected in Standard input code`).
- Static checks: two Woo filter callbacks return the input unchanged; no direct Woo order/config/write calls in source; host/admin/method/environment gate present.
- GitHub Hosted CI: **not claimed**. Earlier PR #120 hosted run failed; local syntax check is **not** Hosted PASS.

Linked: #121, #89, #87 (E2E HOLD), Draft #119, Draft #120.
