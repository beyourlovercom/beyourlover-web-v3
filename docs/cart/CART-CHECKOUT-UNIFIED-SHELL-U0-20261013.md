# BYL Unified Cart → Shipping → Payment · U0 Technical Contract / Dev-only Roadmap · 2026-10-13

## Owner request / reference
- Reference: LELO checkout screenshot `lelo.com/?open=true`: desktop live summary at left, shipping/payment flow at right, compact three progress icons; user wants equivalent interaction feel, smooth mobile UX, no new page jumps or jank.
- Cart V2 mobile footer must remain **non-scrolling**: primary Checkout CTA, compact linked Terms and supported payment SVG row fixed to viewport bottom, only products/recommendations/coupons/totals scroll.
- Existing Owner-approved Woo $10 shipping *estimate* and numeric subtotal/order total must remain displayed; never invent default shipping rates.

## Actual source-level audit (2026-10-13)
**Do not build new payment or shipping engine.**

1. WordPress checkout page ID 13 is classic `[woocommerce_checkout]`, not Checkout Block, route `/checkout/`. Theme `page-checkout.php` already server-renders native Woo shortcode.
2. `theme/beyourlover-v3/woocommerce/checkout/form-checkout.php` contains the complete **BYL progressive Checkout** with existing native Woo address fields, dynamically selected Woo shipping methods, native `#payment` and order summary. Internal stages = `address → delivery → payment → review`.
3. `assets/js/checkout.js` implements step activation, shipping validation, Woo `update_checkout` events, native gateway mounting, review and Woo-owned `form.checkout` submit. Payment gateway native DOM and Stripe/WooPayments iframe lifecycle must remain intact.
4. WooPayments, Apple Pay, Google Pay, Affirm, Afterpay, Alipay, Klarna and Amazon Pay are configured as enabled (availability still depends on currency/device/merchant gateway checks). Never advertise unsupported payment methods or build duplicate payment widgets.
5. Cart Drawer currently `data-byl-cart-drawer` is Dev-preview gated; it loads Woo Store API cart `/wc/store/v1/cart`, serializes cart updates and navigates to `/checkout/` on Secure Checkout. The step icons inside Cart are informational, not interactive yet. The UI currently uses 2 distinct contexts — a Cart Drawer and a full `/checkout/` page.
6. Existing Checkout consent code `checkout.js` has `devPrecheck` that prechecks `cr_customer_consent` (optional marketing consent) and `terms`. **P0 for unified flow**: no default opt-in for marketing; do not assume existing cart Terms link cures direct-to-checkout legal consent. Review jurisdictional enforceability separately before WWW.

## Recommended UI contract
Visible to shopper: exactly **Cart | Shipping | Payment**. Internally retain Woo's required Review as the final substate of Payment, not an omitted security gate.

### Desktop (>=950px)
- Single cohesive checkout shell; left column ~38% **order summary** (cart items, coupon, subtotal, estimated/confirmed shipping, total), right column ~62% **stage panel** with progress icons and selected stage content.
- Cart stage: current item edit and recommendations; Shipping stage: existing Woo email/address fields and `Delivery Method` selection; Payment stage: native Woo payment controls and final Review.
- Native Woo checkout order summary fragments are **single-authority**; never show two independent order totals or duplicate `#order_review` IDs.

### Mobile (M390, M375, M320)
- One stage at a time; fixed height `100dvh` shell with `min-height:0` independent stage scroller and compact **footer CTA + Terms + payment SVG row**.
- Cart step button: `Continue to Shipping`; Shipping step: `Continue to Payment` only after address and shipping rate validated; Payment step: `Review and Place Order` delegated to native Woo only after Woo validation.
- Footer remains safe-area aware, never obscures forms, and collapses while keyboard is active; no duplicated CTA. When payment gateway requires a popup/redirect/3DS, yield control to native Woo and resume safely.
- Country/state/postcode changes invalidate stale chosen shipping rates, recalculate through Woo only, show existing $10 estimate until definitive rate; never fabricate an eligibility award.

## Architecture approach
**Preferred:** server-render unified shell on the existing Woo **Checkout context** (`is_checkout()=true`, Woo scripts and nonces initialized). Include a Cart stage *inside that same checkout document*, then reuse the existing PHP form, JS checkout state machine, Woo summary and native payment DOM without remounting or cloning gateways. Make Cart Drawer `SECURE CHECKOUT` enter the unified shell as a normal authenticated same-origin GET handoff; once there, stage changes are DOM state transitions with no navigation. Do not claim zero initial navigation. In subsequent controlled phase, investigate optional prefetch and View Transitions when account/nonces/PII caching rules allow; never persist checkout HTML with CSRF tokens to shared caches.

**Avoid:** iframe-embedding `/checkout/` inside Cart, AJAX-replacing third-party payment iframe markup, hand-building Stripe/PayPal/ApplePay tokenization, running parallel Woo Classic vs Store API order-submit paths, calling `place_order` outside Woo or changing Checkout route in production.

## Roadmap / rough engineering estimate (excluding external gateway approval)
| Phase | Goal | Approx engineering |
| --- | --- | --- |
| U0 (this document) | Current Cart / Checkout inventory, contracts and rollback gates | 2–3 h |
| U1 | Dev-only unified shell, Cart step + Woo native Checkout staged in same server document, visually aligned desktop/mobile | 6–10 h |
| U2 | Shipping: Woo selected rate, address edit, country changes, postal code, coupon, totals and progress reconciliation; back navigation/fail closed | 5–8 h |
| U3 | Payment + Review: reuse current payment DOM and G5 idempotency/retry/3DS/redirect handlers; explicit test-only gates | 7–12 h |
| U4 | M320/M375/M390/M414 + PC1440 real browser screenshot UAT, keyboard, screen reader, payment method availability, closures | 4–7 h |

Total **24–40 engineering hours** as indicative implementation range; integration/payment edge cases may require more. Only U0 and the independent Cart fixed footer are authorized now; **do not activate a new payment/order flow or deploy to WWW** without separate Owner consent and G5 test restrictions.

## Bounded Dev mobile footer change (already applied)
- Change: `theme/beyourlover-v3/assets/css/cart-drawer.css` only (CSS code includes `M7.1 mobile viewport footer`).
- Predeploy real Dev SHA256 `c30fd653f53a381f51b97825ab280abc0d4bf7b9e8ffff9d7d72b7cb9eca1a78`.
- New live Dev SHA256 `f561b3948ca711079c304d54448a0aa207cf51e2dec038f6aecab418828661bb`, exact single-file guarded Dev-only deploy SUCCESS.
- Isolated rollback `/home/u247343654/domains/dev.beyourlover.com/.m7-cart-mobile-fixed-footer-rollback-20261013/cart-drawer.css`. No WWW, PR merges, Woo orders/payments or options changed.
- Real Windows Chrome CDP visited Dev and loaded **Dev's actual deployed CSS** (no local CSS injection) M390 viewport: scroll area `1236px` content/`590px` viewport, moved scrollTop `0→646`; footer rect top/bottom `729→850`, CTA `740→784`, Terms `791→815`, logos `821→840` **unchanged**. No horizontal overflow (`scrollWidth=390`); six payment SVGs actually loaded. PC1440 GET and visual regression PASS. Cart fixture is isolated browser GET mocked ($254 subtotal, $10 Woo estimate, $264 total); not a real Woo cart mutation.
- Code/visual browser probes in `scripts/manual_cart_mobile_fixed_footer_20261013.js` (candidate source) and `scripts/manual_cart_mobile_fixed_footer_actual_dev_20261013.js` (actual Dev stylesheet). Only browser simulated GET; no orders, payments or cart POST.

## Immediate gate before U1 actual implementation
- Need Owner explicit U1 feature go-ahead for redesigning current Checkout front-end/route context; existing one-time mobile CSS revision consent is not implied permission to modify native Checkout steps/payment.
- Baseline latest GitHub main + Cart PR #80 / previously merged Theme PRs #63/#79 and merged Checkout PR #15; no forced merge or WWW deploy. Preserve M4 BYL49 policy and G5 idempotency ownership; M390 screenshot UAT before Owner Final.
