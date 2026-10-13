# Cart M7.2 Owner correction — ONLY checkout button fixed on mobile
Date: 2026-10-13
Status: **Source + offline candidate UAT PASS; Dev deployment SECURITY HOLD**.

## Owner clarified interaction
This supersedes the incorrect M7.1 rule. On mobile, **only** the Secure Checkout CTA floats at the viewport bottom. The step bar (Cart / Shipping / Payment), product list, recommendation cards, coupon, totals, linked Terms and six payment icons all participate in the **same scrollable content** and must scroll off-screen. Do not pin the step bar or terms/icons. Woo amounts, free-shipping eligibility, backend checkout/payment and campaigns unchanged.

## GitHub code
- `theme/beyourlover-v3/inc/cart-drawer.php`: Cart header/step bar moved **inside** `data-drawer-scroll`; `data-drawer-legal-payment` section (Terms and card icons) after totals, still **inside** the scroll; `data-drawer-footer` contains **only** existing total element (mobile CSS hides it) and the Woo checkout link.
- `theme/beyourlover-v3/assets/js/cart-drawer.js`: `elements.legal.hidden` follows the same empty/loaded status as footer; no Woo Store API mutation.
- `theme/beyourlover-v3/assets/css/cart-drawer.css`: removed M7.1 contradictory sticky header/legal footer rules, added one 100dvh flex shell and separate independent content scroller, tiny fixed CTA-only footer with iOS safe area; steps/legal cards styled within scrolling content.
- `scripts/test_cart_only_cta_fixed_dom_contract.js`: **17/17 PASS**, asserts DOM order, footer content exclusion and fail-closed presentation. Included in Foundation CI.
- `scripts/manual_cart_only_cta_fixed_candidate_20261013.js`: isolated Windows Chrome 390px and 1440px; **browser-only DOM candidate preview + CSS injection + mocked GET cart** (no HTTP cart POST, orders, payments, Woo settings or production changes) PASS.

## Browser evidence
On mobile M390, scrollTop 0 → 694px while:
- Header step icons rect top **65 → -629** (scrolls off-screen).
- Terms rect top **1369 → 675** (moves with content, visible near bottom when scrolled).
- Payment icons top **1404 → 710** (moves with content).
- Checkout CTA top/bottom **796–840 → 796–840** (remains viewport-fixed).
- Footer top/bottom **785–850 → 785–850**; contains only CTA (and hidden desktop total).
- Real cart layout scrollWidth **390** within width **390**, no horizontal overflow; Woo mock $254 subtotal, $10 estimate, $264 total preserved, promotions OFF; six plugin SVGs rendered.
PC1440 checked with the candidate DOM/CSS, no horizontal overflow. PHP syntax PASS, existing Cart mocked Woo regression, M5 progress 13/13, Cart notice/estimate regression PASS. **These are not deployed-file/real-user Owner UAT proofs**.

## Current Dev and explicit blocker
**The corrected PHP/CSS/JS have not been deployed to Dev.** One authorized standard Windows MCP command to build a 3-file Dev-only verified release bundle was blocked by platform security inspection **before running**. We did not retry through another command path or bypass the checker.
Read-only independent Dev SHA check after the block confirms **unchanged previous M7.1 files**:
- Cart PHP `a3af11253263d16e9da7de3be78e93fdf03bc3e197f2a38923844f584aded443`
- Cart CSS `f561b3948ca711079c304d54448a0aa207cf51e2dec038f6aecab418828661bb`
- Cart JS `267c3fe43c745e5171bfdf644ad9cd36d962e30938b8e9290d7ca6248a50a47c`
- Existing off-webroot CSS rollback pointer: `/home/u247343654/domains/dev.beyourlover.com/.m7-cart-mobile-fixed-footer-rollback-20261013/`
- Source branch Draft [Theme PR #80](https://github.com/beyourlovercom/beyourlover-web-v3/pull/80) still unmerged; WWW never touched.

## Next allowed gate
After remote security clears, use **normal approved Dev deployment**, verify Dev staging/host and all three current SHAs match the above; back up each of the three files outside webroot, then install precisely the candidate PHP/CSS/JS. Run PHP lint, CSS/JS hash verification and actual live Dev Chrome M390 scroll: **steps, Terms and cards must move**, only CTA is immobile; desktop 1440 regression; no Woo cart POST or order/payment.
Do not claim Owner UAT PASS or merge/WWW until the user actually confirms the corrected **deployed** result.

## Checkout Terms legal context
The prior Dev-only removal of Woo Classic Checkout Terms checkbox is a product choice, not proof of enforceable legal clickwrap in all jurisdictions. If direct-to-Checkout or direct Payment entry is possible, a conspicuous linked assent disclosure should also be adjacent to the **final payment/Place Order** button, with auditable consent evidence designed under jurisdiction-specific legal review. No new Woo order metadata or payment changes made during this fix.
