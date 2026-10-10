> **LATEST RUNTIME STATUS (2026-10-10): DEV code-only entrypoint deployed and independently verified; Owner visual UAT HOLD because G2/P13 Permit became PRESENT during final preflight. No further Dev operations while G5 may be active.**

## Actual controlled Dev-only rollout — performed
- Owner issued continuing authorization to advance T2 to the point requiring intervention. Confirmed exact PR #70 candidate HEAD `6fafad284a4f4b0cb0ad424ba8a3b3ffdec72ac1`, GitHub Foundation CI `38055646954` SUCCESS; main stayed `16d1cec21840aeee3036d95d57cdf5f1d7eba54a`. Neither PR merged.
- Protected Dev `functions.php` before SHA256 `6a335c1bfc7f5de45a18f2dfbb698e3581a5cc369ca1098d8199dd691772edfb` was backed up and independently hash verified outside webroot at `/home/u247343654/byl-v3-staging/theme-os-t26-pr70-backup-20261010-6fafad2/functions.php`.
- Source staged outside webroot and each SHA256 checked, with PHP syntax + JSON parsing. Under G2/P13 Permit ABSENT and unchanged G5 MU Hash, added exactly three Theme files: `inc/theme-os-bootstrap.php`, `inc/theme-os-assets.php`, `theme-os.json`; atomically replaced **only** Theme `functions.php` last. No existing Dev components, Cart Drawer PHP/JS/CSS, Woo plugins, MU plugins, database or WWW changed.
- Expected/current Dev hashes: `functions.php` `a3fb03e5887f462de14f0d1463c8bca62210fd28eb01dd2e153e9d931eb1a828`; bootstrap `7905b57d63a5f8a8c9c3f9c0bed79654f71a5ae42f870d8656831f4dc4a6ee0e`; assets `8fe0964954864b260b63a377e6e5633a43d9222087b730ba60d3cd91499e43af`; manifest `0ff9a5a456cf95b857f97619675103298ebb36303bdb33dcc3a7a58d88928b4b`.
- Post-deploy **independent** SSH verification confirmed all four hashes, original backup, PHP lint PASS, active Theme 0.1.0, Dev Home/Shop/Cart/Checkout/Track Order HTTP 200, Cart Drawer assets matching the prior deployed PR #63, G5 MU safety SHA256 `8766416cba7e3651ec4f7656fcad9705da832d30adedfb08238e646776f8f137` unchanged.
- The staged deploy Bash script emitted its success summary but then terminated **exit 1** because of a trailing Windows CRLF blank line (`$'\\r': command not found`). This was a script-formatting issue after the installation and checks, **not** a claimed clean script exit. The separate server-state and HTTP verification above establishes the actual deployed outcome. Do **not** re-run the script: its old-state precondition is no longer true.

## Real Dev browser checks and parallel G5 finding
- US Mac fresh headless Chrome profiles, no Windows G5 browser session, no form submissions. M390 and PC1440: Home, Shop, PDP, free shipping, Track Order, Privacy, empty Cart and empty Checkout redirect to Cart all render HTTP 200 with no horizontal overflow; **16 screenshots** held in isolated US Mac test directory `/tmp/byl-theme-os-t25.yWaKVw/dev-t26-readonly-evidence`.
- Strict unmodified browser run: **52 PASS / 4 FAIL**. Both widths produced `TypeError: Failed to construct 'URL': Invalid URL` in `woocommerce-payments/dist/woopay-direct-checkout.js?ver=11.2.0`; script attempted **two POSTs to `/cart/null` per viewport**. All POSTs were **aborted by browser interception**; **0 Woo order or payment submission**. This must be triaged by the independently owned **G5/WooPayments** workstream; not silently passed or corrected in T2.
- Controlled diagnostic **only in the test browser** (same real Dev code, abort WooPayments `woopay-direct-checkout.js` fetch, still block all POST and non-Dev origins): **56 PASS / 0 FAIL**, zero other page errors, zero POST attempts, 16 viewport screenshots. Evidence `/tmp/byl-theme-os-t25.yWaKVw/dev-t26-woopay-isolation-evidence`. This confirms Theme scope under that diagnostic but does **not** validate WooPayments production behavior.
- Empty Checkout redirected to Cart; **filled live Dev Checkout/paid/3DS/Order Received not tested** (no real cart/business mutations permitted). Existing Dev Theme modules diverging from GitHub were deliberately preserved.

## Last safety state and hard stop
- Final **read-only** Dev SSH hash check found all four Theme hashes correct and prior Cart Drawer assets + G5 MU unchanged, BUT the private G2/P13 Permit had become **PRESENT** (earlier ABSENT). The G5 owner may have restarted payment Owner UAT in parallel. **T2 therefore ceased ALL Dev operations. Do not roll back, redeploy, switch theme, inspect live cart session or trigger page UAT while this permit is present.**
- Resume only after G5 owner/Owner confirms the G5 payment window is complete and the Permit is absent, and after rechecking Theme exact hashes and independent Owner visual UAT scope. PR #70 remains Draft/unmerged, PR #69 and PR #63 unmerged. T2.6 **code deployed/auto visual isolated**, but **Owner UAT HOLD**, T2.7 Closure not authorized.

---
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

**Disposition: Dev-only Theme entrypoint code deployed with hash-verified rollback pointer; no Theme merge/WWW release. Final T2.6 Owner visual UAT remains HOLD while G5 Permit is present.**
