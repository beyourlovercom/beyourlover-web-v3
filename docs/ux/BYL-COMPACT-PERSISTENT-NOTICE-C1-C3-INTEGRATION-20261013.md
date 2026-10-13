# BYL Compact Persistent Notice — C1+C2+C3 Integration Candidate

Date: 2026-10-13. Status: **GitHub Draft source-only integration, not Owner final visual PASS, not approved for main merge, not release/deploy**.

## Input branches (preserve source snapshots)

- C1 Cart PR #86 exact HEAD `307ec2fc0c76a3fbe5e36c06c0f69c8da0c62718`, earlier Cart Owner visual UAT PASS. Native Woo Cart top-level notices, N6 mutual exclusion, Dev GET opt-in.
- C2 Checkout PR #88 exact HEAD `140b3286eb71fe2ce0ee6b98ef0e511f467dbe3c`, repaired CSS pseudo selector grouping and CI #38378982215 SUCCESS; **updated C2 visual Owner reconfirmation pending**.
- C3 Pay for Order PR #91 exact HEAD `bcfc3fbd03f19392c930962ab2b227a65186e78c`, repaired CSS pseudo selector grouping and CI #38378335358 SUCCESS; **C3 Owner visual UAT pending**.
- This branch is based on GitHub main `168b0c2fd7b6c2451025a358acf159c0d7131d35`, which has source-only PR #84 already merged.

## Integration resolution

Three input PRs were developed independently against main and each modified the *same* bootstrap, manifest, strict Theme OS tests, notification file-count assertion and Foundation CI. Sequential merges as-is risk losing an opt-in route or breaking the exact bootstrap contract.

This single Draft integration candidate includes **all three source modules and standalone C2/C3 fixtures**, with:
1. An ordered bootstrap appended after existing Cart Drawer, in sequence C1 Cart → C2 Checkout → C3 Order Pay. No original hook registration moved.
2. Theme OS Notification presentation-module inventory of **20 files** (12 pre-existing plus C1 2, C2 3, C3 3).
3. Strict bootstrap and manifest count regression updated once for the integrated ordering and count.
4. Three independent PHP/JS guard/visual tests plus one combined isolation regression in Foundation CI.
5. Real WooCommerce checkout, order verification, coupon/shipping and payment authority untouched; **none of the three CSS routes is enabled by default**.

## Dev state and rollout control

Prior independent, gated Dev Canary work already installed opt-in C1/C2/C3 module and static visual preview files, with exact SHA, off-webroot backup/restore and no-cookie/GET browser checks. C2/C3 pseudo-element-width defect was corrected and offline/browser testing added. **This new integration PR performs no additional Dev or WWW deployment**; Dev-specific runtime may remain ahead of main until future authorized merge.

Do not assume C1/C2/C3 source PRs were merged: they remain Draft. At final integration gate, explicitly decide whether to merge this consolidated PR only and close/supersede the originals; **never merge all four PRs**. Recheck the latest main HEAD, CI, files and source origin before any such decision.

No real order-pay URL with customer keys is needed for static Owner C3 UAT. The warning wording comes from WooCommerce and must remain exact:
"You are paying for a guest order. Please continue with payment only if you recognize this order."

## Remaining gates (not passed yet)

- Owner visual reconfirmation of **C2 updated-width** Dev preview
- Owner C3 Pay for Order visual PASS on isolated Dev fixture
- Consolidated integration Foundation CI PASS and code review
- Separate Owner exact-head GitHub main merge authorization
- Separately approved real Checkout/Order Pay runtime functional UAT; do not imply a static preview proves payment safety
- WWW deploy separate explicit authorization; WWW SSH prohibited.

No WWW deployment, production writes, real payment, order data, newsletter/email, G2/G5 changes, device restart or permission changes in this PR.
