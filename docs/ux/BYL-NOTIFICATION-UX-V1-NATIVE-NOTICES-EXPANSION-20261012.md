# BYL Notification UX V1 — Native Woo / WordPress Notices and Empty States (scope extension proposal)
Date: 2026-10-12. **Design proposal only**; no live Cart/Checkout/runtime integration or Owner visual PASS.

## Why this is required
Owner provided a PC screenshot of `https://dev.beyourlover.com/cart/`: native empty-cart message is a long pale-grey `woocommerce-info`-style strip with a burgundy top border and independent Return to shop action. This is neither the compact success Toast nor the desirable empty-cart state.

Source audit against GitHub main `1325969335a6fd656e47b96d078b312242a5420e`:
- `theme/beyourlover-v3/page-cart.php` renders `[woocommerce_cart]` without custom empty-state markup. This means the empty-cart markup is Woo-owned at runtime.
- `theme/beyourlover-v3/assets/css/cart-focus.css` styles both `.woocommerce-message` and `.woocommerce-info` with identical burgundy top-border/icon. Native `.woocommerce-error` does not have a consolidated design variant there.
- `theme/beyourlover-v3/assets/js/cart.js` has persistent coupon `showError()`; `assets/js/checkout.js` has persistent `message()` and `showPromotionError()`; these must remain durable.
- Notification N5 preview currently only demonstrates synthetic Toast + inline coupon error. It **does not implement Woo native notice unification or the real empty-cart layout**.

## Precise taxonomy (avoid conflating WordPress and Woo)
- WooCommerce classic front-end `wc_print_notices` defaults to three types: `error`, `success`, `notice` (markup `.woocommerce-error`, `.woocommerce-message`, `.woocommerce-info`). Its notice type list is filterable; `warning` is **not** a built-in fourth classic type.
- WordPress admin notices support `error`, `success`, `warning`, `info`. Admin notices are not publicly restyled as part of customer-facing BYL Theme V1.
- Store API / Woo Blocks may have separate client-side notice stores, not automatically equivalent to classic `wc_add_notice`. No blanket interception/assumed mapping.

## Notification OS semantics and recommended display
| User-facing message | Meaning | BYL presentation | Persistence |
|---|---|---|---|
| Confirmed Cart Add / Remove or coupon success | transient verified success | compact burgundy Toast, short line, 2000ms | transient, only after server truth |
| Invalid coupon, insufficient stock, address validation, payment or Checkout failure | recoverable blocking error | field/form-related inline error; icon, text and retry path | persistent until corrected or authoritative state change |
| Warning: stock risk / action limitation | advisory or user action required | inline/section notice in muted amber + neutral text | usually persistent, never obscures CTA |
| Informational shipping, policy, status | backend-authoritative information | inline notice or stable status | state-owned; not automatically dismissed |
| Empty Cart / Empty Wishlist / No Results | stable absence of content | structured Empty State (not Woo `.woocommerce-info` bar and NOT Toast) | persists until content changes |
| Important confirmation | irreversible/high-impact action | Dialog / bottom sheet with focus management | user decision, not timer |

## Empty Cart concept, coordinated with Cart owners
- Replace the presentation of native empty-state area with a prominent empty shopping-bag icon, heading `Your cart is empty`, one sentence description and a **single** shop CTA.
- Optional genuine recent-views group (up to 3 actual products, never synthetic), omitted when no verified recent views. Respect availability and any Cart D4–D7 recommendations ownership.
- On mobile keep content center-aligned, no full-width old Woo notice stripe or adjacent float-only button. Avoid fake products, prices and marketing slots. No Gift.
- Preserve `[woocommerce_cart]`/native Woo session, cart count, checkout flow, totals and business validation. The interface proposal is separate from removing/changing Woo cart code.
- Cart page is owned by Cart workstream. **Do not edit** `page-cart.php`, `cart-focus.css`, Woo templates, or Cart PR #63 without a new scoped integration approval. No hidden suppression of error/action links.

## Notice-specific styling requirements
- Distinct semantic success/error/warning/info tokens, not one shared burgundy color for every state. Icon + clear prefix/description; no color-only identification.
- Error `role=alert`, informative status `role=status` where appropriate; preserve link/button accessibility and native Woo validation; long messages wrap and remain readable.
- Prevent duplicate message rendering if confirmed actions become Toast. Do not blindly remove Woo notices: dedupe only at explicitly owned caller/adapter with a verified Woo response.
- Safe CSS scoping within BYL storefront (not `wp-admin`), no universal body or all-`.woocommerce` cascade overriding payment providers.
- Preserve checkout failure errors, guest Cart errors and network timeout retry behavior. No Woo API mutation and no Woo session writes from presentation.
- Explicitly differentiate `Free shipping available` from selected `Free shipping applied`.

## Proposed addition to existing N0–N8 phases (no new plugin or branch)
- **N1.1**: message/empty-state 6-state component visual baseline for PC1440, M390 and 360; Owner design choice.
- **N2.1**: isolated, reusable Notice / Empty State CSS/HTML, with component contract tests on Notification branch; no global load.
- **N3.1**: accessibility, long notice text, screenreader semantics, action link preservation and persistent errors.
- **N4.1**: complete existing Foundation CI, Theme OS registration and ZIP reproducibility. Add no WC business behavior.
- **N5.1**: only a *separately approved* opt-in Dev Mock of Notice/Empty State, preserving safety gate and exact SHA.
- **N6**: explicitly authorized Cart integration on a bounded isolated branch, after verifying Cart PR #63 current HEAD, resolving Theme OS/CI overlaps, Dev–Prod DB isolation and rollback. Native Cart empty state and Cart success messages can join thereafter.
- **N7**: Owner UAT for Toast, Notice, Inline Error, Status, Empty State and Dialog at PC/iPhone, exact business truth.
- **N8**: gated, page-by-page rollout plan to Checkout / Wishlist / Account, each page owned by its workstream; no implicit WWW deployment.

## Acceptance before marking Notification UX V1 complete
1. Cart empty is **not** success/error and remains until cart contents change.
2. All three core Woo classic notice types have distinct BYL semantic visuals; optional warning variant is explicitly BYL-derived.
3. Woo, WordPress admin and Blocks notice stores are not conflated.
4. Native error text/actions are never swallowed by 2-second toasts.
5. Preview-only demonstration cannot count as real Woo integration or Owner PASS.
6. Existing Cart / Checkout / G2 / G5 / payment, shipping, Gift OFF, Theme OS CI and WWW are untouched until gates.

Status: **PROPOSED DESIGN ADDENDUM — no implementation, no UAT assertion.**
