# BYL Notification UX V1 — N5.1 Owner Visual PASS & N6 Read-Only Gate

Date: 2026-10-12 (Asia/Shanghai). Recorded after Owner replied **"pass"** to the unified six-message preview and final screenshot / live-link handoff.

## Scope and meaning of PASS
- **Owner visual design UAT: PASS** for the existing Dev-only unified Message Lab (short Toast, classic Woo/BYL success/error/warning/info *visual examples*, inline error, status, empty Cart/Wishlist/Search, confirmation Dialog).
- This PASS is **visual review of the mock**, not live Woo storefront, WordPress Admin notices, production deployment, Cart/Checkout integration, end-to-end transaction, or physical iPhone Safari validation.
- Current preview: https://dev.beyourlover.com/wp-content/themes/beyourlover-v3/notification-preview-v1.php?byl_notification_preview=1
- N5.1 technical evidence: `docs/ux/BYL-NOTIFICATION-N5-1-UNIFIED-MESSAGE-LAB-EVIDENCE-20261012.md` and screenshot set under `docs/ux/evidence/notification-n51-*.png`.
- Previous exact approved visual preview + evidence feature HEAD: `2c60fba227f05cbb64b68eca3d74fa712972ce82`. GitHub Foundation CI run [#38195578590](https://github.com/beyourlovercom/beyourlover-web-v3/actions/runs/38195578590) SUCCESS.
- No implicit authority to merge PR, install new plugin, write Woo Cart/Checkout business data, deploy WWW or alter G2 permits.

## N6 read-only source gate (fresh on 2026-10-12)
- **GitHub main**: `1325969335a6fd656e47b96d078b312242a5420e`, sole target source. Confirm again before any N6 source change.
- **Notification Draft PR #73**: source HEAD `2c60fba227f05cbb64b68eca3d74fa712972ce82`, Foundation CI PASS.
- **Cart Drawer Draft PR #63**: source HEAD `30ee230d7e1001cc21afd3c374d8c50431c5ff89`, Foundation CI [#38192653448](https://github.com/beyourlovercom/beyourlover-web-v3/actions/runs/38192653448) SUCCESS.
- Both PRs remain OPEN/DRAFT/NOT MERGED. No unauthorized main merge.
- File ownership overlap between PR #73 and PR #63: `.github/workflows/foundation-ci.yml` and `theme/beyourlover-v3/theme-os.json`. Reconcile only in a separately approved isolated integration branch; do not copy or force-push either draft PR.
- `theme/beyourlover-v3/assets/js/cart-drawer.js` on PR #63: `request()` submits Woo Store API requests, rejects non-2xx responses, requires `result.items` array, renders accepted data, clears feedback; on failure it restores last accepted Cart UI and shows persistent error. Serial `mutationQueue` returns boolean success to callers. Example coupon path uses `update('apply-coupon', {code})`; button paths add/remove/quantity update have no Notification bridge today.
- N6 must introduce opt-in, Cart-owner-authorized adapter *after validated accepted response*, and must not infer server success from a click. Preserve persistent error and retry, prevent duplicate native notice + Toast when adapters own confirmation, and preserve Woo prices/cart counts/coupons and payment boundaries.
- `page-cart.php` on main renders native `[woocommerce_cart]`. The genuine empty-cart UI, original `.woocommerce-info` and `Return to shop` remain unchanged at Dev and require separate Cart-owned integration. N5.1 style mock must not be claimed as live empty Cart rollout.
- Build single integrated CI, Theme OS fail-closed file ownership and ZIP reproducibility; do not bypass Foundation or short-circuit pending checks.

## Explicit N6 prerequisites before real Woo Guest Cart canary
1. Separate Owner authorization for N6 isolated Cart + Notification integration; **visual "pass" is not this approval**.
2. Recheck exact two PR heads and CI at integration start; resolve both overlapping files with controlled isolated branch and full CI.
3. Verify Dev/Production DB and business-data separation without writing or reading Production secrets; if not proven, stop before any real Woo mutation.
4. Confirm G2 checkout closed, host isolation, scoped Dev backup, rollback commands and no hidden WWW paths.
5. Use only approved synthetic/guest Dev Cart scenarios; do not create Checkout/order/payment, email, shipment, Gift, or modify account data.
6. After exact-code Dev canary and observed failures/success transitions, obtain distinct N7 live Owner UAT. Merge, WWW deployment and release still require separate authorization.

## Next stage
N0–N4 component development/tests DONE. N5 static Dev preview technical PASS. **N5.1 mock visual Owner PASS.** N6 read-only source gate prepared but **integration authorization PENDING**. N7 live Owner UAT pending. N8 closure pending.
