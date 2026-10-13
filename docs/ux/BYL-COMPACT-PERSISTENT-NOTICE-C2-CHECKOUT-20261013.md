# BYL Compact Persistent Notice — Checkout C2 controlled Dev preview

Date: 2026-10-13. **Draft source only** until verified Dev deployment gate. 
Owner Cart C1 visual UAT PASS; C1 PR #86 remains Draft/unmerged. Source PR #84 was merged to main.

## Scope
- Checkout C2 exclusively; **not** Cart, Order Pay C3 or Thank You.
- Native Woo persistent top-level notices within `#main-content > .woocommerce` or its direct `.woocommerce-notices-wrapper` only. CSS never targets Checkout field errors, promo-input status, payment-gateway subtrees, Woo Ajax NoticeGroup, Toast, mobile action footer, shipping amounts, tax, or order totals.
- Default OFF. Exact Dev host + GET + `is_checkout()` + explicit `?byl_compact_notice_preview=checkout`; disallow order-pay, order-received, WP admin, Ajax, REST, POST, WWW and malformed flags. No normal-route activation.
- Preserve complete Woo source text, severity, links and accessibility roles. Browser-local synthetic messages may be used for CSS visual tests; they are never real Checkout errors or authorizations.
- CSS: 13px body, 1.38 line height mobile, 7px vertical mobile padding, 16px icon, 3px start rail, full wrap; action min-height 44px.
- Existing Checkout `.woocommerce-message:has(.wc-forward)` suppression is unchanged by C2; this PR does not alter Woo notice generation.
- WooCommerce remains checkout, payment, stock, order, coupon, tax, shipping authority.

## Integration governance
- Independent PR branch based on GitHub main to prevent silent merging of unfinished Cart PR #86. This may produce a bootstrap/Theme OS test integration conflict when C1 merges later; merge reconciliation will require renewed main SHA/CI.
- Dev currently contains a **locally deployed unmerged C1 bootstrap**. C2 Dev-only installation must surgically append new require to the exact verified existing C1 file, never overwrite it with main's C2 source. Record both pre/post SHA and test both flags.
- Confirm Dev/Production host isolation, G2 OFF, full off-webroot backup and isolated restore rehearsal, exact input files, unchanged unrelated protected files and rollback pointer.
- No WWW SSH/deploy, DB migration, real order or payment, newsletter, cart mutations, device restart, PR merge. GitHub main remains sole ultimate merge target.
- Read-only browser UAT must pre-arm interception of **all non-GET requests before navigation**. Do not create payment/order/coupon test fixtures; only insert synthetic local DOM notices after GET.
- After Owner C2 visual PASS, proceed to independently gated Pay for Order C3 with Woo's original Guest Order safety notice and authorization unchanged.
