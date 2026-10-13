# M7 Cart + Checkout Owner Revision — Dev-only UAT · 2026-10-12

## Explicit Owner changes

Owner **rejected** the previous no-address "Calculated at checkout" display after seeing it prevent a numeric total. Restore Woo's existing initial shipping charge ($10 in observed destination example), retain a visible *estimated* order total before the shipping address is confirmed, use the top reminder and add a local shipping/import duty/tax reminder immediately before Subtotal.

Owner also requested: move the Terms acknowledgement from Woo Classic Checkout checkbox to the Cart Drawer bottom under Secure Checkout; remove the Checkout Terms checkbox; present one tidy payment-brand row at the very bottom, ideally reusing existing BeYourLover payment assets. **All changes Dev-only**, no WWW, no PR merges, no real order/payment.

## Final UI implementation
- `theme/beyourlover-v3/assets/js/cart-drawer.js`: if Woo returned a numeric estimate, show `Shipping (estimated) $10.00` and `Order total (estimated) $264.00` for screenshot fixture `Subtotal $254.00`; footer shows Woo total $264.00. Genuine selected rate and usable address may instead be marked confirmed; M5 "free shipping applied" still cannot be promised without shipping address + Woo rate.
- Immediately **before Subtotal** (not before products or at huge top): compact left-accent warning: "Please check shipping costs, import duties and local taxes for your country or region. Final charges depend on your delivery address."
- `theme/beyourlover-v3/inc/cart-drawer.php`: replace "Shipping and payment are confirmed securely at checkout." with *"By clicking Secure Checkout, you agree to the Terms and Conditions of purchase."* Clickable Terms link to Woo terms page `11735`. Below this statement, one line of card/payment icons referencing **existing first-party WooPayments plugin SVGs** (Visa/Mastercard/Amex/Discover/Apple Pay/Google Pay, where source file exists). Do not add unsupported PayPal branding: Dev gateway audit did not verify it enabled.
- `theme/beyourlover-v3/assets/css/cart-drawer.css`: mobile-friendly small body copy, subtle orange local duty box, compact linked Terms below CTA, payment icons row last. Mobile checkout CTA retains sticky behavior.
- `theme/beyourlover-v3/inc/checkout.php`: **Dev hostname only** Woo Classic Checkout terms template is suppressed via `woocommerce_checkout_show_terms` and server-side checkbox-enabled contract via `woocommerce_get_terms_and_conditions_checkbox_text` returning empty. On WWW both filters fail closed and original terms checkbox/validation are preserved. Woo `[woocommerce_checkout]` classic shortcode confirmed; no Woo core, Checkout WordPress page post, DB option or terms page changed.

## Tests and Dev execution
- Exact versioned code tested on Windows PHP/Node offline: 7/7 shipping-estimate fixture scenarios; 13/13 M5 fail-closed progress cases; original Woo mocked quantity/coupon/retry/negative-safety regression PASS; **9/9 Dev-only terms and production-safety assertions PASS**; JS syntax + both modified PHP templates lint PASS.
- Dev preflight = WordPress `staging`, `dev.beyourlover.com`, Woo terms page ID `11735`, Classic Checkout; native Woo T&C checkbox initially enabled. Six plugin SVG HTTP GET 200 at preflight.
- Controlled *exactly 4-file Dev-only deploy*, prior file hash guard, PHP lint, independent off-webroot rollback at `/home/u247343654/domains/dev.beyourlover.com/.m7-cart-terms-backup-20261012/`. Deployment completed `M7_DEV_CART_TERMS_PAYMENT_FOUR_FILES=PASS`. Exact new SHA256:
  - `assets/js/cart-drawer.js` `267c3fe43c745e5171bfdf644ad9cd36d962e30938b8e9290d7ca6248a50a47c`
  - `assets/css/cart-drawer.css` `c30fd653f53a381f51b97825ab280abc0d4bf7b9e8ffff9d7d72b7cb9eca1a78`
  - `inc/cart-drawer.php` `a3af11253263d16e9da7de3be78e93fdf03bc3e197f2a38923844f584aded443`
  - `inc/checkout.php` `759a8e7f0a697859bf3bb66cc190904ecb3e801b8c8af1d6ef5e0f6d76e6ac16`
- **Actual WP Dev check without `--skip-themes`**: Woo `wc_terms_and_conditions_checkbox_enabled()===false`; `woocommerce_checkout_show_terms=false`; Woo T&C checkbox template output 0 bytes; existing real Woo terms page remains available. No real order or payment submission made, so actual end-to-end order success not verified.
- Real Dev homepage/Cart Drawer/Checkout GET 200; HTML includes new linked Terms and existing plugin SVGs, JS code includes tax reminder and numeric estimates.
- Windows **real Chrome CDP mobile viewport 390px + desktop 1440px** with GET-only isolated mocked Woo cart (no POST/real cart changes) showed exact `$254.00/$10.00/$264.00`, Terms visible, local charges reminder before subtotal, five loaded payment brands at capture time and 6 supported icon tags, with no horizontal overflow. Screenshot code-only reproducible under `scripts/manual_cart_terms_payment_m7_dev_viewports.js`. Desktop and mobile render visually coherent. Do not misstate mocked cart values as a real-customer checkout/order UAT.

## Legal and delivery gates
- User requests the acknowledgement be at the **Cart CTA**, not as a mandatory Checkout checkbox. This design is a product choice and **requires legal/compliance confirmation before any production enablement**: customers can potentially navigate directly to Checkout without viewing this Cart, so mere Cart notice might not prove affirmative assent to the terms of sale in all jurisdictions. The Dev implementation is not a legal determination. Privacy/shipping policy links remain available.
- No WWW updates, no GitHub merge, no checkout payment/order POST, no live marketing promotions, no DB changes, no READY device restart.
- Theme [Draft PR #80](https://github.com/beyourlovercom/beyourlover-web-v3/pull/80) remains unmerged and requires exact-head Foundation CI plus separate Owner approval before main. Cart v2 original PR #63 / Theme T4 PR #79 remain separate governance.
- **Owner UAT pending**: Open `https://dev.beyourlover.com/?byl_cart_drawer_preview=1` (hard refresh) with items to verify shipping estimate, duty reminder, linked Terms and logos; navigate GET-only to `https://dev.beyourlover.com/checkout/` to verify no separate Terms checkbox. Do not Place Order. Final M7 may only PASS upon Owner confirmation; M8 remains HOLD.
