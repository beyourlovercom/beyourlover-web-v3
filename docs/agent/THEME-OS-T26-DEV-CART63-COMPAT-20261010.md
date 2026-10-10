# BeYourLover Theme OS T2.6 — Cart Drawer PR #63 Dev compatibility gate

Date: 2026-10-10. This is an **isolated integration proposal**, NOT a main merge, Dev deployment, Theme 1.0.0 release or G5 permit.

## Live Dev preflight (read-only)
- Connected via existing BYL self-hosted Windows Remote MCP using configured `byl-dev-hostinger` SSH (no browser or WWW interaction).
- Dev: WP 7.1.3, PHP 8.3.35, WooCommerce 11.2.1, YBY/Andy Core 1.9.0, Andy Commerce 1.0.1, WooPayments 11.2.0, Stripe 11.0.1, AfterShip 1.18.4; active BeYourLover V3 0.1.0.
- Actual Dev `functions.php` SHA256: `6a335c1bfc7f5de45a18f2dfbb698e3581a5cc369ca1098d8199dd691772edfb`. **Exact SHA match** to independent, unmerged Cart Drawer PR #63 HEAD `30e1e97709db6370e89d2f25783e6fe73db785aa`.
- Dev Cart Drawer PHP, JS and CSS also match PR #63 exactly. The plain T2 PR #69 `functions.php` would drop its include: **do not deploy plain PR #69 to Dev**.
- Dev G5 safety MU-plugin SHA256 `8766416cba7e3651ec4f7656fcad9705da832d30adedfb08238e646776f8f137`; private G2/P13 permit file was **ABSENT** at last read-only preflight. This is time-sensitive and MUST be checked again before any Dev change; G5 test owner is separate.
- Several other *existing* Dev Theme modules differ from GitHub main/PR #63: components, homepage, archive, product, product-modules, checkout, cart-experience, VI tokens, cart CSS and Cart JS. These must be **preserved and never bulk-overwritten**. No claims of full stack parity.

## GitHub source-only composite
- GitHub main T1 baseline `16d1cec21840aeee3036d95d57cdf5f1d7eba54a`. T2 PR #69 source at `6a62e1d33eb227c142aced2c01f0fadd2b8b12ed` plus Cart Drawer PR #63 HEAD `30e1e97709db6370e89d2f25783e6fe73db785aa` were combined in *scratch* Git worktree and copied to a **separate non-mergeable-by-default Draft integration branch**.
- Conflicts resolved in `functions.php` and Theme OS contract test. The bootstrap preserves all **10** existing Dev Cart Drawer ordered includes; asset callback preserved byte-for-byte (4,946 bytes).
- Verified actual old Dev/PR63 `functions.php` against composite: exactly **two** hunks of substitution (10 ordered require statements → loader; existing enqueue callback → asset file), **zero other modifications**, unchanged free-shipping include.
- Theme OS manifest has **75** Theme-owned files in 12 modules. Cart Drawer JS/CSS/PHP remain Cart-owned and Preview gate stays Dev opt-in, gift OFF.
- Offline combined candidate: **27 CI gate groups PASS** (incl. G2/P13 safety, P5 StoreAPI Checkout deny, P7, Woo Cart, Theme OS registry, Drawer runtime mock).
- Real independent WordPress 7.1.3 / Woo 11.2.1 Chrome: **M390 + PC1440 80/80 PASS**, supplementary native quantity/coupon/invalid-receipt **12/12 PASS**, 0 Woo orders, 0 PHP warning/fatal, 0 JS page errors.
- Theme ZIP builds twice identically, verifier PASS, 75 files, version 0.1.0. Pre-document isolated ZIP SHA256 `66259de03fcc67e249db09143fdbd0d1a52ad4823a08e9de01a01a19091ef150`; Git receipt refers to scratch-only commit `7198308f6e53f6313f44fa3dc2ceb97cdf58a162` before this Markdown evidence commit. Regenerate at latest GitHub exact-head before any release.

## Hard stop and Owner UAT requirements
1. Keep this PR Draft; neither PR #63 nor #69 nor this integration has independent merge authorization. GitHub `main` remains unchanged.
2. No plain PR #69 Dev deploy. Source-only composite tests do **not** demonstrate compatibility with all installed Dev versions, because some active Dev Theme modules diverge.
3. Before *any* Dev-only code operation, recheck exact current Dev Theme SHA, G5 permit absence, no active payment 3DS, no concurrent deployment, and a verified scoped code backup/rollback. **Stop if any condition fails**. Never modify G5 MU plugin, G2/P13, gateways, DB, real orders, mail, ERP, AfterShip, Cart Drawer assets or WWW.
4. A later permitted, file-scoped deployment would add static loader/asset files and manifest, and replace `functions.php` **last**, preserving all existing Dev module files and hooks. Perform native Cart/Checkout GET only, M390/PC1440 visual UAT; no order submit/payment/3DS.
5. T2.6 Owner Dev UAT and T2.7 Closure remain pending. Independent exact-head PR merge and future WWW approval remain separate.

**Disposition: GitHub integration proposal/test evidence only; actual Dev deployment not yet performed.**
