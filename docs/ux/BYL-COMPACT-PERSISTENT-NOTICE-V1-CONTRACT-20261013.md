# BYL Compact Persistent Notice V1 — source-only handoff

**Date:** 2026-10-13  
**State:** V2 isolated Dev visual lab Owner UAT **PASS**; component source is **Draft PR only**; no Cart/Checkout/Pay for Order runtime activation, no WWW deployment.

## Scope and ownership

- Owner-approved real-browser visual samples: PC1440, M390, M320; five page contexts (Cart, Checkout, Pay for Order, Thank You, Global); 15 notice examples and 6 exclusions; Original ↔ Compact toggle. No non-GET browser requests.
- **Code-confirmed**: Cart shipping-promotion states and coupon field errors from `assets/js/cart.js`; Checkout progressive-step messages and inline promo errors from `assets/js/checkout.js`; Pay for Order guest safety notice originates in installed WooCommerce `includes/shortcodes/class-wc-shortcode-checkout.php`; Thank You pending/failed state from `woocommerce/checkout/thankyou.php`.
- **Dynamic or illustrative**: native Woo coupon errors, gateway declines, tax/duties, and policy copy are *not* asserted as exact customer-facing messages. Numeric free-shipping amounts are examples. Revalidate against Woo and approved policy before rollout.
- WooCommerce owns cart, stock, shipping, coupons, checkout, payment and order authority. Andy Commerce owns its B2C promotion configuration. This component is **visual only**.

## Opt-in component contract

1. Load existing `assets/css/notification-surfaces.css`, then `assets/css/notification-persistent-compact.css`. The latter is **not enqueued** by this change.
2. Add class `byl-notice--compact` to an *existing verified persistent* `.byl-notice` surface **only after** a separately reviewed integration. Example: `class="byl-notice byl-notice--warning byl-notice--compact"`.
3. Mobile: 13px body, 1.38 line-height, 7px vertical/10px horizontal padding, 16px icon, 8px gap, 3px emphasis rail. Desktop: 8px/12px padding, 1.42 line-height.
4. Semantic variants: success, error, warning, info. Preserve existing DOM roles (`status`, `alert` as appropriate), action and link content, focus, scrolling, and untruncated text; interactive controls remain at least 44px high.
5. No automatic dismissal. No override of transient right-in/right-out Toast, inline field errors, modal/dialog, native payment block, or full Thank You payment-status page state.
6. Dynamic long notices may grow vertically; a fixed height or line clamp is forbidden. Do not shorten legal, customs, guest-order or payment safety text without an independent content approval.

## Safety gates / next stage

- Independent Draft PR and Foundation CI (offline source contract). No PR merge without exact-head and new CI Owner approval.
- **Do not** inject CSS broadly via `.woocommerce-error`, `.woocommerce-info` or `.woocommerce-message`. Do not change Woo/checkout hooks, error payload, session, DB, G2, G5, or payment flows.
- Integration requires explicit, route-specific review by Cart V2 and Checkout/Pay for Order owners; note Dev WooPayments previously emitted `POST /cart/null` even on passive Cart navigation, so do not perform unguarded browser E2E as a read-only test.
- Dev backup/rollback, isolation and precise SHA mandatory before runtime CSS deployment. WWW (different server) stays untouched; WWW SSH prohibited.

## Visual gate evidence

- Owner `pass` following Dev Notification Message Lab V2 preview at `/wp-content/themes/beyourlover-v3/notification-preview-v1.php?byl_notification_preview=1#demo-v2-scenarios`.
- Isolated browser test checked 3 screen sizes × 5 page contexts, compact/original switch, long text, zero JS errors, zero non-GET requests.
- V2 preview is a separate Dev-only mock. GitHub code is the sole merge authority; the current PR does **not** switch the Dev preview assets into production runtime.
