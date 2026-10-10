# BeYourLover Theme OS V1 — T2.3 Woo native adapter audit
Date: 2026-10-10. Evidence: GitHub main `8223932b740012037f0e5f30a5d4b7322d35721a` plus T2 stacked PR #69 on T1 PR #65. **Read-only source audit; no Dev/production payment validation**.

## Ownership / results

| Touchpoint | Observed actual code | Native owner and decision |
|---|---|---|
| Classic Cart | `page-cart.php` delegates via `[woocommerce_cart]`; `assets/js/cart-experience.js` uses native form submit/fragment refresh; `assets/js/cart.js` requests `wc/store/v1/cart/apply-coupon` and `remove-coupon` with `wc_store_api` nonce | Woo owns Cart Session, cart rows, coupons, totals, fragments. Theme handles presentation. No parallel cart session observed in audited files |
| Checkout | `page-checkout.php` uses `[woocommerce_checkout]`; `inc/checkout.php` filters Woo checkout fields and disables old coupon form on the checkout page; `assets/js/checkout.js` retains native payment #payment container and Woo checkout submit | Woo + payment gateways own order creation, tax and callbacks. Theme must never manufacture a successful payment response |
| Checkout assets | `inc/theme-os-assets.php` retains `jquery` and conditional `wc-checkout` dependencies; Store API URLs + nonce localized only on checkout excluding received | Keep dependencies and nonce exactly; missing wc-checkout retains baseline fallback. No new Store API endpoints |
| Order received | `inc/thank-you.php` registers receipt style separately; `functions.php` asset callback explicitly excludes `is_order_received_page()` | Woo order/payment state remains authoritative. G5 is separately gated |
| Cart promotion | `inc/cart-experience.php` reads Woo shipping packages, zones, calculated rates, selected shipping method, coupon session and Andy Commerce persisted policy to render a read-only native promotion state | `threshold/subtotal/remaining` are UI projections only, not mutation of Woo shipping fee or eligibility. Avoid repurposing into payment rules |
| Recommendations / PDP | `inc/cart-experience.php` uses `wc_get_products`, stock/status and real Woo cross-sells; `inc/product.php` calls Woo image, availability, related product and structured data APIs | Native Woo products, stock and prices stay authoritative |
| Product archive | `inc/archive.php` installs Woo `woocommerce_product_query` hook priority 20 and reads `andy_custom_order` / `andy_display_limit` term metadata | Existing query business behavior is coupled to Theme. **Risk for later explicit migration**, not safe to move as part of behavior-preserving T2 refactor |
| Checkout shipping phone | `inc/checkout.php` uses native `woocommerce_checkout_fields` filter priority 9999, assigns required shipping phone field and validation | Native Woo validation is used, but business field-policy ownership should be reviewed with Andy Commerce in T3; this T2 branch must not change it |

## Security and nonregression
- No changes to Checkout JavaScript, Woo hooks, PHP business functions, payment gateway configs, WP order or cart data in T2 source refactor.
- G2/P13 Permit and G5 Windows 3DS test state must be checked separately before any Dev runtime install; nothing in CI proves a real payment.
- At the T2.4 code-only level, route smoke must fail if global assets bleed into Cart/Checkout or if native dependency, localized Store API URL or nonce changes. Existing G2 P13 16-case and P5 51-check security CI gates remain in the Foundation job.
- Archive catalog order and shipping phone field ownership are **tracked findings**, not a license to change business policy in this PR.

## Outstanding runtime evidence
1. Capture installed versions of WP/PHP/Woo, Andy Core/Commerce and gateways from a later read-only Dev check; do not assume them.
2. Perform isolated WP/Woo functional rehearsal with nine routes, real checkout fragment behavior, Cart redirects and no payment; record M390 and PC1440 evidence.
3. Dev-only install (if later separately authorized) requires exact installed Theme hash, file backup, rollback, G2/G5 safety isolation and Owner UAT. Neither audit nor Foundation CI authorizes Dev/WWW deploy.

Conclusion: **T2.3 source-level native adapter audit completed with two nonblocking future-governance findings; real WP integration remains unverified.**
