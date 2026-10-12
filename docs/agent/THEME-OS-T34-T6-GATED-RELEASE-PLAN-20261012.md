# BYL Theme OS — T3.4 to T6 release gates

Date: 2026-10-12. GitHub main baseline: `67142ae5b453306f70c422be636ac572a1d498ff`.

## T3.4 — exact plugin stack

Read-only Dev inventory: WordPress 7.1.3, PHP CLI 8.3.35, WooCommerce 11.2.1, YBY Core / Andy Core 1.9.0, Andy Commerce 1.0.1, WooPayments 11.2.0, Stripe 11.0.1, PayPal plugin 2.0.27. GitHub YBY-Core main `2cdae873d3b0831a942c6a9e95ef8f151a9ecc26` contains Andy Core 1.9.0 but packages/andy-commerce/andy-commerce.php declares **1.0.0**, not Dev's 1.0.1. Never substitute 1.0.0 for 1.0.1 and call exact plugin-stack testing PASS.

To finish T3.4: identify exact 1.0.1 source/hash via authorized read-only route; build a separate WP/Woo/Andy Core/Commerce environment using only synthetic data; prove hook compatibility, variant, shipping, cart, Checkout invalid nonce, and absence of unexpected writes. Existing T3.3 did verify real WP/Woo 19/19 in a private temp database but did not include both Andy plugins.

## T4 — release package readiness

Use clean exact GitHub main SHA. Rebuild Theme ZIP twice and verify bit-identical SHA256, receipt, per-file hashes, no symlinks, no unexpected files, and SemVer. Preserve installed Dev Cart Drawer code and include: Cart PR #63 remains independently Draft/unmerged. Current clean main theme must **not** be deployed over Cart-enabled Dev. Prepare a composite artifact with a distinct approved change set when dependencies are ready. Rehearse rollback only in an isolated filesystem/DB. Do not tag v1.0.0 until authorized.

## T5 — Dev visual and functional Owner UAT

After an independently authorized full Dev candidate, inspect on M390 and PC1440: Home, Shop, simple and variable PDP, Cart, Checkout (safe/invalid-only), read-only Thank You, Free Shipping, Track Order, Newsletter, Page/Post and policies. Capture screenshots, console/network and performance evidence. Never mark automated success as Owner Visual UAT PASS. Home/cards visual FAIL is explicitly deferred to #76. WooPayments native Invalid URL / cart/null case is not passed by diagnostic suppression.

## T6 — WWW cutover gate

WWW is a private server and WWW SSH is **not available or requested**. Verify WWW code/plugin versions through permitted UI/admin method without assuming Dev/Production share server/DB. Validate an existing relevant WWW backup for code+DB and its restore pointer; reuse a valid backup rather than creating unnecessary duplicates. Explicit Owner Final UAT and release/cutover approval must reference exact Theme SHA, package SHA256, rollback reference, accessible deployment mechanism and maintenance window. Do not use a generalized proceed instruction to bypass these safety/Production gates. After separately authorized cutover, perform read-only post-cutover tests; roll back on fail. No payments, orders, user data, mail, ERP, G2 or READY device reboot as part of this Theme technical plan.

## Current decision

T3.4 exact full-stack test: PENDING (source version drift). T4 packaging planning: PREPARED, release/tag NOT authorized. T5 Dev Owner UAT: NOT STARTED for a compatible joint release. T6 WWW cutover: BLOCKED by independent Production gate. Continue safe test/documentation preparation without stopping at routine steps.