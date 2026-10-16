# BYL Checkout Visual Polish V2 — Track A
Date: 2026-10-16. Source-only independent Draft, **not deployed**.

## 0. Owner baseline and zero-regression rules

Use the definitive archived screenshots in the user's private Library bundle `BYL-Checkout-Canonical-V1-Design-and-Handoff.zip`: `Shipping-Owner-PASS-Mobile.png`, `Payment-Owner-PASS-Mobile-Desktop.png`, and `Email-Guest-Exact-Reference.png`. These are Owner-locked; avoid redistributing original references or uploading actual cart screenshots that contain personal data into public GitHub. The style is BYL original burgundy, black text, PingFang/system fonts, white backgrounds and subtle dividers. Exactly **Cart → Shipping → Payment**, not a fourth Review step.

Native WooCommerce is authoritative for cart/session, actual shipping and free-shipping selections, taxes and totals, coupon handling, eligible gateways, nonce, legal consent and 3DS. Design screenshots' example amounts, card logos, tax ID and Express delivery claims are NOT valid operating configuration.

## 1. Workstream isolation

**Track A (this branch):** browser-visible Shipping/Payment card padding, typography, visual hierarchy, responsive safe-area, admin bar occlusion, summary expansion, visible payment-copy de-duplication and Woo-rate-truthful free-shipping message.

**Track B (other window):** gateway activation/eligibility, WooPayments/PayPal/Stripe, provider button initialization, 3DS/payment runtime. Do not modify provider settings, Woo native payment controls, transaction flow, customer data or backend business rules in A.

Source starts from Draft PR #120 exact HEAD `f096267eb260f77b792c09ed94cbdfd06ad1817b` in independent branch `feature/checkout-visual-polish-v2-a-20261016`. No changes to PR120's source branch; `main` remains the only final merge target. PR120 and predecessor #119 are currently unmerged.

## 2. Implemented as a separate narrow visual-only layer

- New `assets/css/checkout-visual-v2.css` styles ONLY the Dev Drawer body class; never affects plain `/checkout/` fallback or WWW. Keeps one mobile CTA and total bar, 1010px desktop right Drawer width, removes logged-in WordPress toolbar overlap, and refines form labels, focus rings, shipping cards, native wallet container spacing, order summary lines and the Payment total/CTA grouping.
- New `assets/js/checkout-visual-v2.js`: read-only DOM observer. If the promotion service says Free shipping unlocked, do **not** repeat an unconditional claim when the Woo selected method is paid; show 'applied' only if the native selected Woo shipping method for every package is `free_shipping:*`; if real free option is offered, suggest selecting it; otherwise tell the customer the rate depends on the delivery address. Does NOT recalculate or change any rate, tax, coupon or total.
- Only suppress the exact redundant BYL-generated no-method message if the equivalent native Woo warning is visibly present; other provider, address and legal notices must stay visible. The actual gateway enablement issue belongs to Track B.
- Clicking Payment 'See details' must open or preserve the existing Woo summary; not close it when already expanded. A signed-in 'Edit your profile' link is presented as quiet 'Account', preserving the same destination.
- Only `inc/theme-os-assets.php` and `theme-os.json` changed for registering these two new asset files under the existing staging-only `byl_v3_checkout_drawer_enabled()` guard. Does not modify Woo form PHP, checkout JS, provider SDKs, Cart Drawer or checkout routes.

## 3. Verification — independent offline evidence, NOT Owner Final

- `python3 scripts/test_checkout_visual_v2_contract.py`: source safety and enqueuing gate, no gateway/order/payment/Store API write.
- `NODE_PATH=/tmp/byl-lelo-l3-dom-uat/node_modules node scripts/test_checkout_visual_v2_dom.cjs`: actual native Woo free/paid rate selection vs promotion message, safe missing-rate fallback, exact-match warning de-dup only, signed-in account link, order details open, no fetch/payment writes.
- `NODE_PATH=/tmp/byl-drawer-visual-pw/node_modules BYL_CHROME_BIN='/Applications/Google Chrome.app/Contents/MacOS/Google Chrome' node scripts/manual_checkout_visual_v2_chrome.cjs`: M320, M390, simulated iPhone14 viewport, PC1440. Checks zero offsite requests, no JS exceptions, one Woo form/CTA, no horizontal overflow, CTA stable on scroll, desktop exact right-edge/1010px, admin bar hidden behind drawer. Images in `docs/checkout-visual-v2/fixtures/SYNTHETIC-LOCAL-*.png` are SYNTHETIC ONLY (not real gateway/buyer data).

## 4. Unfinished before genuine 100% Owner approval

- Real Dev checkout with an existing populated Woo session at M320/M390/iPhone14/PC1440; compare side-by-side against the ORIGINAL Owner references. Synthetic screenshots do not qualify. No payment attempt.
- Payment ordinary methods absent in real Owner Dev screenshot: Track B must independently resolve provider activation/eligibility; no fake method logos/buttons in A.
- Shipping Owner design contains tax ID, email marketing offer consent, Save Address and country customs notices. These are still independent backend contracts; no fake forms for pixel-matching. Woo real rate does not prove an Express SLA.
- Cart→Shipping still involves one native route GET. Strict no-navigation handoff is a separate unresolved P0; do not claim it complete.
- Owner visual PASS does not authorize WWW deploy, main merge, payment or database changes. Cart Owner Final, Checkout Owner Final, G5 evidence, #87 E2E and T6 remain independently gated.

## 5. Next safe release gate

This Draft's runtime allowlist is exactly 4 files: existing `inc/theme-os-assets.php`, existing `theme-os.json`, **new** `assets/css/checkout-visual-v2.css`, **new** `assets/js/checkout-visual-v2.js`. Previous PR120 Dev-only approval does NOT cover this branch. Require NEW exact HEAD Owner Dev-only authorization, approved one-time local CI exception if hosted GitHub CI remains blocked, verified old Dev SHA, off-webroot backups, atomic same-filesystem replace, SHA/HTTP/browser smoke and rollback. No WW, real orders, payments, customer/DB/mail writes or READY restarts.

Roadmap: A0 Owner reference PASS → A1 visual source PASS → A2 isolated DOM/Chrome matrix PASS → A3 Draft PR pending → A4 authorized Dev Canary pending → A5 real screenshot Owner Final pending; Track B and WWW HOLD.
