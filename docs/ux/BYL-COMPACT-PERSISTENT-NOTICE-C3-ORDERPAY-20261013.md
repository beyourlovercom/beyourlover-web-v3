# BYL Compact Persistent Notice C3 — Order Pay visual preview

Date: 2026-10-13. Source-only Draft PR until Owner C3 visual UAT, separate merge approval.

## Owner gates
- Cart C1 Owner Visual PASS, PR #86 Draft / unmerged.
- Checkout C2 Owner Visual PASS, PR #88 Draft / unmerged, CI SUCCESS. Both previews Dev-only.
- C3 is independently based on GitHub main. It does not merge C1 or C2 automatically.

## C3 route
- Only an existing WooCommerce **order-pay** route, as determined by `is_checkout_pay_page()`, on `dev.beyourlover.com` for explicit GET `?byl_compact_notice_preview=orderpay`.
- Disabled by default; never enabled on WWW, admin, AJAX, REST, POST, order-received / Thank You or other routes.
- CSS scoped to `body.byl-cpn-orderpay-preview.woocommerce-order-pay #main-content.byl-commerce-content > .woocommerce` direct-child native Woo notice containers, not payment widgets or customer information.
- Preserve WooCommerce notice message (including complete Guest Order warning), severity, order authorization, order key checks, guest email verification, address confirmation panel, controls, links and gateway logic. No text replacement, form changes or dynamic Woo hooks.
- Style tokens: 13px, mobile top/bottom padding 7px, 16px icon, 3px rail, no clipping, 44px minimum control height.
- The standalone static Dev visual fixture contains the exact known Guest Order sentence but *no* real customer/order data and never boots WordPress. CSP disables network and form actions. Original ↔ Compact comparison uses the actual C3 stylesheet.

## Dev deployment requirements
1. Recheck exact GitHub main, Draft PR head and Foundation CI.
2. Dev currently has locally deployed unmerged Cart C1 and Checkout C2; do **not** replace the current bootstrap with the main-based file in this PR. Verify current bootstrap SHA, append C3 only, and preserve all previous routes.
3. Off-webroot backup, isolated restore rehearsal, exact SHA of candidate files, PHP syntax, fail-closed G2, no WWW access. Atomic apply and immediate rollback on error.
4. Deploy **no live payment/order fixture**. Test the standalone visual fixture and route protection using no-cookie GET. Any order-pay functional test is a separate Owner authorization step requiring a synthetic approved order and network write guards.
5. No shipping/cart/order/payment/data/email mutation; no Woo checkout request, no Production deployment or READY device restart.
6. A future release requires reconciliation of PR #86, #88 and this C3 PR against latest main, new CI, explicit Owner merge authorization and separate real-page acceptance.

## Final integration readiness
This PR proves default-OFF source, isolated CSS and mock visual UAT only. It does not claim actual payment verification, nor enable Compact Notice globally.
