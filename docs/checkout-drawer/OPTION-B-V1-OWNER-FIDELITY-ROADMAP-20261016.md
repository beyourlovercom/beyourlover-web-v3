# BYL Checkout Drawer Option B — Owner Fidelity Roadmap V1
Date: 2026-10-16 | BYL Website V3 | Issue #89 | Draft branch only

## Owner exact visual baseline, do not redesign
The original Owner-approved screenshots were verified against the archived Library reference bundle in:
BYL Website V3 / Checkout LELO Canonical V1 / BYL-Checkout-Canonical-V1-Design-and-Handoff.zip.

Authoritative design reference names:
- Shipping-Owner-PASS-Mobile.png
- Payment-Owner-PASS-Mobile-Desktop.png
- Email-Guest-Exact-Reference.png

Cart → Shipping → Payment ONLY. BYL original burgundy, black typography, PingFang, white surfaces, thin separators, very compact payment gateway list, one bottom sticky CTA mobile, order total + See details above CTA, no added fourth Review step. These Owner images are visual baselines, not real payment amounts or authorized gateway settings. The external LELO reference graphics are not embedded in this public GitHub repo.

## Implemented by this isolated source branch
- Reuses existing Dev Cart Drawer. Its secure checkout link points to the Dev-only native route /checkout/?byl_checkout_drawer=1, rather than the normal full-page checkout. The unmodified /checkout/ remains the fallback.
- On this *native Woo checkout document*, the complete original Woo Classic form (nonce, gateway host, shipping rates, checkout processing, Terms and G5 state) is rendered exactly once inside a visible right-side offcanvas Drawer. No iframe, remote checkout content fetch, duplicate form or self-created payment controls.
- Desktop Drawer follows Cart Drawer dimensions: right-edge width min(1010px,78vw), full height, same dark scrim. Summary/products/contact/total left; eligible native Woo gateway inputs and sole Pay Now CTA right.
- Mobile Drawer fills 100dvh, scrollable content, header/close, Cart–Shipping–Payment icons, correct active/completed labels; only one fixed CTA at bottom with fixed order total/See details above it; 320px / 390px no horizontal overflow.
- Shipping → Payment uses existing single-document Checkout step controller. Payment back delegates to Shipping. Esc, backdrop, back navigation and focus trapping use native DOM. Existing Cart Drawer reopens on previous storefront page after exit using one-shot presentation-only sessionStorage flag, with no PII.
- While native Woo payment/3DS submission is pending, manual Drawer exit is blocked. Provider redirects remain owned by Woo and are not intercepted.
- The separate user-approved PR #119 three-step fields, phone-before-ZIP, email input without helper, optional apt/organization, native shipping rate selection are reused. No fake shipping price/SLA or provider logo.
- Dev-only capability is checked by Woo checkout context, staging environment, dev.beyourlover.com host, and exact opt-in query. Order Pay/Thank You excluded. WWW gets no Drawer activation.

## Exactness gates — NOT all completed
| Requirement | Source / local fixture | Actual Dev Owner PASS |
|---|---|---|
| Cart/Shipping/Payment only | Implemented | PENDING |
| Visual Drawer like Cart PC/mobile | Implemented | PENDING |
| Mobile fixed CTA, total/details and scrolling | Isolated M320/M390 PASS | PENDING |
| Desktop summary left, methods right | Isolated PC1440 PASS | PENDING |
| Exact guest email / phone / optional address fields | Reused PR #119 | PENDING |
| Real Woo methods/rates, native 3DS, Taxes/FX, legal consent | Woo-owned source | PENDING |
| True Cart → Shipping **zero-navigation same document** | NOT IMPLEMENTED: one secure top-level GET is still required | HOLD |
| Country Notices and post-ZIP tax IDs | NOT IMPLEMENTED; requires Andy Core + Woo validation | HOLD |
| Marketing subscription / save address / auto-account notification | NOT IMPLEMENTED; requires authorized backend & Mail OS | HOLD |
| Original approved screens 100% pixel/interaction Owner signoff | NOT CLAIMED | PENDING |

### Known architectural difference
This branch implements a **real offcanvas Drawer on the secure native checkout route**, not an iframe, and the Shipping/Payment transition has no new navigation. However the initial Cart checkout click navigates once from the product/cart page to that secure route. The original requirement for zero-hop from Cart is **still unmet** and must be treated as a separate P0 gateway-provider compatibility issue. Never call this completely implemented or 100% passed until resolved or explicitly accepted by Owner.

### Local visual evidence is simulated (no Woo transaction)
- fixtures/LOCAL-FIXTURE-M320-Payment.png
- fixtures/LOCAL-FIXTURE-M390-Payment.png
- fixtures/LOCAL-FIXTURE-M390-Shipping.png
- fixtures/LOCAL-FIXTURE-PC1440-Payment.png

These show the actual new CSS on a clearly labeled local fixture with non-authoritative product/gateway/total examples. Manual Playwright Core script: scripts/manual_checkout_drawer_option_b_visual.cjs. It tests 320/390 mobile width, PC1440, no horizontal overflow, one form/CTA, fixed CTA stability while scrolling, right-edge panel and correct active stage. The synthetic fixture is NOT actual Dev runtime UAT.

### Release and integration gates
This branch starts from approved PR #119 head, which is Dev-only deployed but unmerged in main. GitHub main is still the sole final merge target. Do NOT merge this branch before reconciling #119. Previous four-file PR #119 Dev-only deployment authorization does NOT apply to the newly changed files here. Explicit new exact-head, per-file Dev-only approval, backup/rollback and legitimate tool permission required. No WWW, orders, payments, DB/settings/mail writes or READY-device restarts.

After eligible Dev Canary: real M320/M390/iPhone14/PC1440 screenshots, Woo gateway/Terms validation, country rate switching and native session recovery. Owner may issue final UAT PASS only after side-by-side screenshots match the approved Owner images and all P0 gaps have been resolved or consciously accepted.
Commerce E2E #87 and WWW T6 remain HOLD.

Roadmap: Owner design PASS → three-step PR #119 Dev deployed only → Option B source Draft+local visuals → Dev Canary approval/UAT → zero-hop + country/account rules → Owner Final → #87/T6.
