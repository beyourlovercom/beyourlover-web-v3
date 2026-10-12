# BYL Notification UX V1 — N0 Source Inventory / N1 Visual Baseline
Date: 2026-10-12. Scope: notification feature branch only; code and mock, no Dev/WWW deploy.

## GitHub snapshot (verified before implementation)
- main: `1325969335a6fd656e47b96d078b312242a5420e`.
- Notification branch was 1 commit ahead and 0 behind main, containing only the contract.
- Cart Draft PR #63 OPEN/unmerged: `75ca8df18a237252a73623a471efd4114d94ab19`. The Cart Drawer JS/PHP/CSS are *not* in Notification branch.
- Baseline Theme OS manifest `theme-os.json`: N0 ownership inventory source.
- Any future changed main or PR HEAD requires a new check before N5/N6.

## N0 source/trigger matrix
| Owner / source | Visible messaging | Authority and severity | V1 action |
|---|---|---|---|
| main `assets/js/cart-experience.js` | native Woo cart form update | Native Woo form and notices | LEAVE; no optimistic success |
| main `assets/js/cart.js` | coupon `showError`, shipping UI state | Store API, selected shipping authoritative | LEAVE; persistent error/status |
| main `assets/js/checkout.js` | `message()`, `showPromotionError()`, `checkout_error` | Woo Checkout/payment/Store API | LEAVE; must remain persistent |
| shell `assets/js/shell.js` | native search dialog / overlay | Navigation UI | Avoid focus/overlay collisions |
| PR #63 `assets/js/cart-drawer.js` | `setFeedback(message,error)` and Woo request | API response used to render; error remains inline | **N6 ONLY** bridge confirmed successes, never pending or failed response |
| WooCommerce native notices | server notices/errors | WooCommerce business truth | No global interception or blanket suppression |
| Theme OS `theme-os.json` | module file ownership | Registry/ZIP contract | Register Notification module with unique ownership |

## N0 conflicts and safety observations
1. Cart PR #63 currently clears non-error feedback upon accepted Woo response and writes error feedback to the same node. Success bridge must be invoked **after** `response.ok`, parse, and validated Woo Cart JSON; failures keep a persistent retryable inline message.
2. Coupon errors and stock/address/payment errors cannot be replaced with a 2-second toast. Native Woo notices remain available.
3. Free Shipping 'available' is not equivalent to a selected free shipping rate. Notification must never generate shipping claims.
4. D3.3 Cart sticky header / floating CTA remains solely in Cart branch. Notification neither owns nor modifies this layout.
5. Gift remains OFF. No plugin, Woo, G2/G5, Newsletter, payment or production write.

## N1 UX contract / design handoff
- Brand: `var(--byl-brand)` burgundy, light icon/text. Information uses neutral ink, never LELO purple.
- Mobile target: one line + icon; height about 40 px; horizontal safe margin 12–20 px; ellipsis, accessible full label.
- Motion (LTR): right -> visible 230ms, hold 2000ms, exit through viewport left 230ms.
- Only one visible toast, at most three waiting; same-key dedupe within 1200ms; queued feedback older than 4000ms is dropped.
- Keyboard focus and non-Cart dialogs suspend display; pagehide cancels timers. Reduced motion changes CSS transition, not text readability.
- Product business success: `BYL.notify({type:'success',confirmed:true,context:'cart',key:'...',message:'Added to your cart'})` *only* from a verified response adapter.
- Persistent errors/status: `BYL.notify.inlineError(node, message)` / `BYL.notify.status(node, message)`; callers retain Woo truth.
- Mock: `docs/ux/byl-notification-v1-preview.html` (file-based, synthetic actions only). Baselines PC 1440x900, M390x844, 375/360 width follow-up.
- Preview is not live Woo verification, a screenshot or Owner UAT. Human iPhone Safari requires N5/N7.

## N5–N8 gates (not authorized by this commit)
N5: host isolation+rollback and exact SHA/CI, separate Dev-only deployment approval.
N6: PR #63 dependency and separate Cart integration approval; guest Woo session only, never checkout/payment.
N7: Owner visual/touch validation in physical iPhone and PC; explicit `Notification UX V1 Owner UAT PASS`.
N8: record exact CI evidence and rollout plan only after gates. Merge main / WWW require separate approval.
