# BeYourLover Theme OS V1 — T3 compatibility and isolated upgrade gate

**Date:** 2026-10-11 PT. **Source of truth:** GitHub `beyourlovercom/beyourlover-web-v3` main at T3 start `67142ae5b453306f70c422be636ac572a1d498ff` (Theme OS T2.7 merge PR #77; main push Foundation CI #38211872873 SUCCESS).

## T3.0: observed Dev baseline (read-only; NOT a support certification)

Observed via Dev SSH / WP CLI with `--skip-plugins --skip-themes`. This queries installed versions and plugin state without executing the active plugins/theme.

| Component | Dev observed | Current verification | Explicit limit |
| --- | --- | --- | --- |
| PHP CLI | 8.3.35 | Read-only `php -v` | FPM/SAPI parity, host extensions and edge versions not independently checked |
| WordPress | 7.1.3 | `wp core version` | No full WordPress upgrade/E2E conducted |
| WooCommerce | 11.2.1 active | `wp plugin list` | Version detected, not certified cross-version compatibility |
| YBY Core / Andy Core | Plugin slug `yby-core` 1.9.0 active | Plugin inventory | Actual Newsletter/Mail runtime integration not exercised here |
| Andy Commerce | 1.0.1 active | Plugin inventory | Coupon, discount/BOGO business runtime not activated by T3 |
| WooPayments | 11.2.0 active | Plugin inventory | Previous native Dev `woopay-direct-checkout.js` Invalid URL / blocked `POST /cart/null` remains a separate exception; no payment PASS |
| Stripe | 11.0.1 active | Plugin inventory | No gateway payment tests |
| PayPal plugin | `pymntpl-paypal-woocommerce` 2.0.27 active | Plugin inventory | No PayPal payment tests |
| BYL Theme | 0.1.0 active on Dev | Theme inventory | Current GitHub main is **not yet deployed** to Dev and differs from Dev's Cart Drawer-enabled bootstrap |
| Isolated macOS CLI | PHP 8.3.33 | `php -v` | Offline suite runs against mocked WordPress/Woo hooks, not the full installed Dev stack |

No assertion is made about vendor minimum/maximum supported ranges. Those require official headers/support matrices and live test evidence.

## T3.1: ownership and regression boundary

- **WooCommerce authoritative** for item price, stock/variations, Cart Store API totals, selected shipping rate, Checkout, order and payment. Theme must not duplicate business rules.
- **YBY Core** owns common WordPress infrastructure; **Andy Commerce** owns B2C promotions, shipping policy, gifts. No Shoptimizer/CommerceKit dependency or child theme.
- Theme runtime keeps eager ordered PHP bootstrap and one scoped `wp_enqueue_scripts` callback. Offline route fixture covers Home, Shop, PDP, Shipping, Track Order, Cart, Checkout, Thank You, and generic route.
- Cart and Checkout nonce localization points to `wc_store_api` and uses Woo native Store API routes. Tests verify scoping, dependencies and callback fallbacks **under mocks**, not real invalid/expired nonce handling.
- PDP source reads Woo `wc_get_product()` and `is_in_stock()`; variations/stock race and user sessions **not live E2E-tested** in this stage.
- Dev has a separately installed Cart Drawer whose PR #63 is still unmerged into GitHub main. **Do not deploy plain main/PR #77-derived ZIP directly over Dev** or its include would disappear. A future composite release must preserve its separately approved active loader.
- Previous Dev M390/PC1440 80/80 browser checks were scoped to earlier T2 Dev code with the native WooPayments failing script temporarily blocked **only in the test browser**; they are not a current-main full Dev browser pass, nor a visual Owner PASS.
- Owner-approved T2 **technical-only exception** does not erase Homepage/cards **visual FAIL**, assigned to independent Issue #76.

## T3.2: repeatable isolated update and rollback

Automated offline test: `python3 -m unittest discover -s scripts -p test_theme_os_t3_upgrade.py -v`.

The tests use **temporary filesystem trees only**. They build/check a Theme-only ZIP with receipt; verify that the receipt keeps `production_deploy=NOT_AUTHORIZED` and `owner_uat=NOT_GRANTED`; compare all member SHA256 values to extracted staging files; simulate a synthetic older theme directory with deliberately altered temporary style and an extra file; copy a lossless rollback snapshot; swap the staged files in the temporary directory, then restore the exact snapshot; and show a corrupted receipt aborts before replacement. They verify original Theme source files remain unchanged. They do **not** activate WordPress, run DB migrations, modify themes on Dev/WWW, or validate live Woo plugin compatibility.

## T3.3 remaining evidence gates (do not mark PASS until executed)

- [ ] Actual WordPress/Woo runtime compatibility in a **separate isolated installation**, with exact versions; check compatible PHP APIs/extensions and active Theme hooks, no Production DB or site-state reuse without explicit approval.
- [ ] Test both simple and variable products, stock available/out-of-stock, and variant selection; test invalid/expired nonces, Woo Cart Store API rate/total authority and back navigation with synthetic datasets.
- [ ] Test Checkout native validation, failures/redirects with **synthetic fixtures only**; G5 real payments, 3DS, provider callbacks and idempotency remain other independent Owner gates.
- [ ] M390/PC1440 page/browser performance and visual parity on the actual **joint Dev release candidate**, while preserving Cart Drawer, Notification, YBY Core and Andy Commerce state.
- [ ] WP/Woo/PHP version upgrade permutations and rollback beyond this temporary file-level rehearsal, subject to independent authorization.
- [ ] Owner T3 Final UAT before advancing T4 release candidate. No GitHub PR merge or tag by implication.

## Governance

T3 is **Dev-off, WWW-off, no order/cart/payment/DB/mail/ERP writes, no G2 permit, no READY device restart**. Source changes belong to Draft review branch until independent exact-head and CI approval. This report deliberately separates **observed**, **mocked**, and **not yet tested** compatibility.
