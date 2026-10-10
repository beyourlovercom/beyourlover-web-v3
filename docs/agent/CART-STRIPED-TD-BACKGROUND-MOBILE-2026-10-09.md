# Cart product row gray rectangle bug — visual UAT fix

Screenshot: Owner showed a gray-white rectangular patch in the middle of the second Cart item row, between thumbnail and name/quantity, while two products were present.

## Root cause — confirmed on real Dev HTML/CSS
WooCommerce 11.2.1 responsive table styles apply `background: rgba(0, 0, 0, 0.024)` to the individual `td` elements in alternating product rows. BYL V3 Cart changes mobile `tr.cart_item` to `display:grid`, and each `td` to a separate grid element. The Woo stripe is therefore drawn as multiple isolated rectangular patches rather than as a whole-row background.

Reproduction: anonymous independent Dev Chrome at 393 CSS px, with product IDs 36089 (Ariel & Lippy) and 36653 (BEAR), confirmed computed background `rgba(0, 0, 0, 0.024)` on six second-row cells. The proposed scoped override `background:transparent!important` on `table.shop_table tbody tr.cart_item > td` changed every affected computed background to `rgba(0, 0, 0, 0)`, retaining the row separation, product image's own soft background, quantity controls and price. The test Cart Session was emptied at end.

## Scope/gates
- BYL V3 Theme CSS only; no plugin or Woo configuration changes.
- Rule is scoped to Cart product-item cells, not cart totals, checkout, images, buttons or coupon fields.
- Regression test in existing `test_cart_vi_tokens.js` ensures the rule cannot be accidentally removed.
- Dev-only deploy after exact file-hash preflight, backup and CI PASS. Check the actual two-product Dev Cart at 393px, plus desktop / no overflow.
- Owner visual UAT is required; no WWW deployment, DB migration, order/payment, inventory changes or Woo/ERP sync. G2 Store API Checkout POST must stay 403.
