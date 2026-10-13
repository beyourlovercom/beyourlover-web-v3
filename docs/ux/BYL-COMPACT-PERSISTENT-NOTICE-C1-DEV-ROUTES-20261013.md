# BYL Compact Persistent Notice — C1 Cart real-page Dev preview

2026-10-13 · **Source-only Draft PR** · Owner approved progression from V2 Message Lab to Cart → Checkout → Pay for Order runtime UAT.

## Scope

- **Only Cart** in C1; Checkout/Pay for Order will be separate gated branches after Cart's visual gate. No change to payment, order, Woo cart business operations.
- Dev-only exact GET flag: `?byl_compact_notice_preview=cart`. Normal requests, WWW, non-Cart requests, POST, Ajax, admin, and malformed array flags remain OFF.
- Existing N6 Cart Notification Bridge preview wins; C1 intentionally refuses simultaneous activation.
- Reuse PR #84 `notification-persistent-compact.css`, and add new isolated `notification-compact-cart.css` with native Woo notice selectors scoped to `body.byl-cpn-cart-preview .byl-cart-runtime`. Do not modify notice DOM, text, links or Woo severity.
- Compact targets native Woo error/info/success banner containers only. The Cart shipping progress bar, promo input inline error, custom Cart Drawer feedback, Toast and empty-cart state are unchanged.
- Approximate design tokens: 13px text, 1.38 line height, 7px vertical mobile padding, 16px icon, start border 3px, 6px radius, 44px minimum action height. Long text wraps completely.
- Theme OS contract and offline fail-closed gate included in Foundation CI. No global CSS enqueue; no release/activation from GitHub merge alone.

## Deployment and UAT safety gates

1. Confirm Dev host / WWW isolation and G2 safety permit OFF.
2. Compare exact Dev bootstrap / file SHA with Owner-approved baseline. It can differ from GitHub; back up original files OFF webroot and rehearse isolated restore; never copy whole theme over Dev.
3. Dev runtime wiring requires an exact file allowlist (`inc/theme-os-bootstrap.php` + new gated PHP + new scoped CSS + PR #84 component CSS) and explicit deploy authorization. Fail closed / restore if mismatches.
4. Do NOT navigate ordinary Dev Cart in an unguarded browser because WooPayments can automatically POST `/cart/null`. For read-only visual UAT, intercept *all* non-GET requests **before navigation** and verify no unintended network writes.
5. No real coupon, item/cart mutation, checkout submission, order creation, payment, email, DB migration, or WWW deployment.
6. After C1 Cart Owner Visual PASS, start separate C2 Checkout and then C3 Pay for Order; each uses specific page routes, Woo message audit, and Dev-only opt-in. Guest Order warning stays original Woo message and error severity.

## Evidence

- Notification V2 Message Lab Owner Visual UAT PASS, 5 contexts/15 samples/6 exclusions PC1440 + M390 + M320.
- Compact source PR #84 GitHub-main merge `b72da480322d15feac23763a98b3d316d720a9d9`, post-main CI SUCCESS.
- Existing Cart N6 isolated bridge remains separate. G5 CLOSED.
