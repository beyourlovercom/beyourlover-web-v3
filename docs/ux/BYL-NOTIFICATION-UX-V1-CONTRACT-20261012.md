# BYL Notification UX V1 — Independent Branch Contract (2026-10-12)

## Status and dependency
- Repository: `beyourlovercom/beyourlover-web-v3`
- This dedicated feature branch was created from main `1325969335a6fd656e47b96d078b312242a5420e`. Main remains the only merge target.
- Cart Drawer is still in Draft PR #63, latest observed head `75ca8df18a237252a73623a471efd4114d94ab19`; it is **not merged into this feature branch**.
- No Notification V1 runtime component, Dev deployment, Owner UAT or WWW release has been completed.

## User-facing UX direction
- Reference: Owner-provided LELO iPhone mobile recording and screenshots. **Copy behavior, not LELO colors or assets.**
- Success/info toast: BeYourLover Theme VI burgundy `var(--byl-brand)`; compact single-line icon and short plain text; right-to-left entry (~200–250ms), hold ~1800–2200ms, exit farther left (~200–250ms). No stacked alerts, no long-lived blocking UI or excessive footer/header height.
- Avoid covering content, keyboard focus, coupon field, bottom floating Checkout CTA. Honor safe areas, orientation, keyboard, focus and prefers-reduced-motion.
- Only emit success toast after Woo/host **confirmed successful** mutation; rejected/stale responses must not produce success.
- Invalid coupons, stock/address/payment problems: persistent inline error with correction path, NOT a short auto-dismiss toast.
- Free shipping: persistent Woo-authoritative status, never false eligibility. Gift selection is separate Dialog/Sheet; actual Gift feature remains OFF.

## Work stages
N0 source notice inventory → N1 spec/visual mock → N2 reusable Theme UI + dispatcher → N3 queue/dedupe/accessibility → N4 offline tests/Theme OS/complete Foundation CI → N5 separately authorized Dev-only opt-in Canary → N6 separately approved Cart PR integration → N7 Owner physical iPhone + PC UAT → N8 closure and staged future rollout.

## Current source touchpoints
- Unmerged PR #63: `theme/beyourlover-v3/assets/js/cart-drawer.js` setFeedback()/Woo request flow, `inc/cart-drawer.php` and Cart CSS.
- Main: `theme/beyourlover-v3/assets/js/checkout.js` progressive Checkout/error message; do not alter payment/G3/G5.
- Follow Theme OS manifest (`theme-os.json`) and deterministic ZIP + Foundation CI; no new plugin without Owner approval.

## Governance
Work on this branch only, retain G5/Newsletter/Theme OS tests and changes from latest main. No implicit PR merge or WWW deployment. No Production DB migration, Checkout POST, real orders, payments, email, changing G2/G5 permit, ERP writes, or restarting READY devices. Dev deploy requires isolation verification, exact SHA/CI, off-webroot backup/rollback and separately applicable approval. Cart D3.3 mobile header / floating CTA belongs to the Cart workstream; do not modify it on this branch without interface coordination. Stop at Owner visual UAT before declaring PASS.
