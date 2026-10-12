# BeYourLover Theme OS T2.7 — pure runtime extraction on latest main

**Purpose:** Prepare a Theme OS-only code change on the current GitHub main, without absorbing the independent Cart Drawer PR #63 or Notification/G5/Newsletter work.

## Governance and Owner decision

- **Main:** `195f10990780cca83030be2444fd0eb123e34f09` (includes Notification UX V1 #73).
- **Cart Drawer PR #63:** independently completed feature work per Owner, but remains Draft/unmerged at `30ee230d7e1001cc21afd3c374d8c50431c5ff89`. This is not a request to re-run Cart Owner UAT.
- **T2:** Owner approved **technical-only exception acceptance**; Dev homepage/product cards remain **visual FAIL**, deferred to standalone Issue #76. No general Owner Visual PASS.
- **New candidate:** pure T2 modularization on main. Does not contain `inc/cart-drawer.php`, `assets/js/cart-drawer.js` or `assets/css/cart-drawer.css`. These are owned only by PR #63.
- **T2 runtime scope:** replace original ordered `require_once` block in `functions.php` with an eager ordered `inc/theme-os-bootstrap.php` and move the original `wp_enqueue_scripts` callback byte-for-byte to `inc/theme-os-assets.php`. Main still owns all existing Woo/G5/Newsletter/Notification code and any production behavior. No new plugin/child theme/business policy.
- **Manifest:** register those exact two new files, update runtime mode, retain all main-owned modules, code and notification assets.
- **Tests:** isolated Theme OS 13-case contracts, WordPress route asset fixture, fail-closed manifest and deterministic checksum package, full offline Foundation CI. Record exact pass state per candidate SHA; unrun items must not be labeled PASS.
- **Critical dependency:** the Cart Drawer is active on Dev. **Do not deploy this pure-main candidate to Dev** until there is a reviewed combined Dev runtime that keeps Cart Drawer loader. Do not alter the Dev installed `functions.php` or disable Cart to test this PR.
- **Separate PR gates:** pure T2 and Cart #63 both require independent exact-head merge approval. No automatic GitHub main merge, Dev/WWW deploy, Theme v1.0.0 tag, Woo payment/order/ERP/DB writes or device restart.
