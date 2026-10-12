# BeYourLover Theme OS V1 — T2.5 Isolated WordPress/Woo Functional Rehearsal

**Date:** 2026-10-10  
**GitHub source:** stacked Draft PR #69, exact code head `719fc84ac836cdb552a6240af1f51a5b5d9f62cf`, based on T1 merged main `16d1cec21840aeee3036d95d57cdf5f1d7eba54a`  
**Gate:** T2.5 isolated technical rehearsal PASS with limitations; **NOT Dev Owner UAT PASS, NOT release/merge authorization**.

## Independent environment

- Device: USA Mac mini, existing BYL self-hosted Remote MCP; never restarted READY device, Chrome profile or existing services.
- Temporary sandbox: `/tmp/byl-theme-os-t25.yWaKVw/`. Source was a fresh authenticated exact-branch clone, not an existing modified checkout.
- WordPress **7.1.3**, WooCommerce **11.2.1**, PHP **8.3.33**. These are **isolated-test versions, not verified versions on Dev**.
- Own MySQL 8.4.11 process, temporary data directory, UNIX socket with `--skip-networking`, no production/dev database connections. PHP dev server on `127.0.0.1:18875` only.
- Woo fixture: 9 local pages, 1 synthetic published simple product, 1 synthetic variable product with 2 variations. No customers, production data, real credentials, orders, gateway/payment operations or ERP integrations.
- WordPress external HTTP is blocked (`WP_HTTP_BLOCK_EXTERNAL`), auto-updates / cron disabled; `pre_wp_mail` intercepted and the test PHP mail binary forced to `/usr/bin/false`. Separate headless Chrome user-data dir; all non-loopback browser requests aborted.
- The theme was activated **only inside this standalone fixture**, never on Dev or WWW.

## Actual HTTP and browser checks

| Test surface | Isolated result |
|---|---|
| Home, shop, simple-product PDP, variable-product PDP | HTTP 200 |
| Empty Cart, free-shipping alias, Track Order, Privacy Page | HTTP 200 |
| Empty Checkout | Woo native 302 redirect as expected |
| Add simple fixture product, Cart and Checkout GET | HTTP 200, session-preserved, native Checkout form |
| M390 + PC1440 headless Chrome | 80 PASS / 0 FAIL / 0 browser page errors, plus 16 width observations |
| Horizontal overflow | document scrollWidth == viewport width at all 16 baseline page checks |
| Cart quantity | Starting qty 1, two quick + clicks persist qty **3** at M390 and PC1440 |
| Woo Cart mutation authority | Exactly one native Woo Cart form POST per double click, no parallel Theme cart total |
| Invalid coupon | Woo rejected synthetic code at both viewports |
| Order Received invalid-order read-only URL | 200 with no Checkout JS enqueue at both viewports; **no order created** |
| Synthetic Woo orders after tests | **0** |
| Isolated WP debug fatal/warning count | **0** |
| Screenshots | 14 local-only PNGs, `evidence/{home,simple,variable,shipping,cart_empty,cart-filled,checkout-filled}-{390,1440}.png` |

Screenshot artifacts and browser JSON results are temporarily stored in `/tmp/byl-theme-os-t25.yWaKVw/evidence` on the USA Mac mini, **not uploaded or treated as an Owner-approved screenshot**. Actual evidence logs: `browser-result.json` (80 pass, 0 fail) and `cart-extended.json` (12 pass, 0 fail). One initial cross-viewport empty-Cart test failed because the test harness reused the earlier session cookie; separating browser contexts removed this fixture contamination, and the full fresh rerun passed. No Theme fix was required.

### Independent observation: invalid Order Received fallback

On a synthetic URL `/checkout/order-received/999999/?key=invalid-fixture-only`, Woo displayed its generic “Thank you. Your order has been received.” text despite there being no corresponding order. The BeYourLover `woocommerce/checkout/thankyou.php` explicitly delegates missing `WC_Order` objects to Woo's `checkout/order-received.php` fallback. This is **not payment success evidence** and appears to be inherited presentation behavior; hand off for G5/postpurchase owner review under its own security scope. **Do not change G5 or order receipt runtime in T2.**

## Scope limitations / remaining T2 gate

- All browser tests ran against isolated WordPress/Woo with **no live gateway, no Dev production-like plugin matrix, no actual WooPayments 3DS, no real orders, emails or ERP callbacks**.
- Isolated Woo/Theme compatibility and Cart-native quantity/coupon GET flows passed. No claim that the actual Dev installed versions or plugin combinations match this sandbox.
- T2.6 **not started**: perform Dev-specific read-only versions + file-hash + plugin audit, check G5/P13 Permit and G5 Windows 3DS completion; only after **independent Owner Dev-only deployment approval**, backup exact Theme files and perform real 390px/1440px Dev Owner UAT.
- Neither T2.5 PASS nor the T1 merge confers permission to merge PR #69, deploy Dev/WWW, change the active Theme, migrate databases, change payment tests, or issue a Theme v1.0.0 release.

**Conclusion: T2.5 isolated technical rehearsal PASS. Next gate: T2.6 Dev-only Owner UAT authorization and safe execution, never overlapping G5.**
