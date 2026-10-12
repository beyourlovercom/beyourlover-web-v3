# BeYourLover Cart V2 — Marketing Slots V1 + D4–D7 Integration Contract
Date: 2026-10-12
Status: Draft PR #63 / Dev-only opt-in / **advertising OFF and Free Gift OFF**

## 0. Source of truth and independent workstreams
- Repo: `beyourlovercom/beyourlover-web-v3`. GitHub `main` is the only merge target, and PR #63 remains an unmerged Draft pending separate Owner approval.
- Only the opted-in Dev preview `https://dev.beyourlover.com/?byl_cart_drawer_preview=1` may load Cart Drawer V2. Existing `/cart/`, G3 Checkout, G5 payments, www and Woo cart backend stay unchanged.
- Notification UX V1 is separately developed on `feat/byl-notification-ux-v1-20261012`: this PR does NOT implement global toast delivery or duplicate its dispatcher.
- Any marketing business policy and activation control belongs to a separately approved Andy Commerce module, not a new plugin or Theme OS settings hack.

## 1. Owner-approved mobile visual layout
1. Non-sticky header with Cart → Shipping → Payment progress status; it scrolls away normally.
2. Woo-only shipping state above cart lines; no invented free-shipping threshold or promo claims.
3. Cart products → You Might Enjoy (3 recently viewed first, rolling 30-day eligible paid sales if lacking) → Marketing Slots V1 (OFF) → promo code → Woo totals.
4. A *single* mobile floating Checkout CTA; no duplicate footer amount or explanatory paragraphs. Coupon focus must not be obscured.
5. No marketing slot causes layout gaps when disabled. iPhone safe-area/keyboard and M390 are P0 review targets.

## 2. Marketing Slots V1 — OFF by design
Two independent presentation surfaces are reserved:
- `campaign_banner`: at most ONE visually prominent campaign, placed *after* recommendations and *before* coupon/totals. Compact copy, optionally one R2 absolute HTTPS image URL and one HTTPS destination.
- `service_note`: one short factual message, e.g. shipping/packaging information only when a verified policy/commerce status supports it. No generic misleading reassurance or invented deadline.
- Implementation today uses a hidden, nested semantic `data-drawer-marketing-slots` container and two hidden children. `marketingEnabled=false` is localized into the Dev preview, and the actual Theme runtime forces the container hidden.
- In current V1: **no banner, no message, no ad requests, no click tracking, no marketing order records, no coupons or free gifts generated, no placeholders consuming pixels**. The future business integration requires a new Owner activation gate.
- Future Andy Commerce *proposal* (NOT implemented, and not a license to turn features on): active flag default false; campaign ID, single placement, start/end UTC window, verified target zones/languages, priority, plaintext or escaped copy, R2 absolute image, same-origin/allowlisted HTTPS CTA, policy source, audit author and owner authorization, rollback to OFF. Never derive activation from URL params, cookies, localStorage, ad scripts or synthetic Store API fields.
- Ads must not cover the cart product actions, coupon, keyboard, iPhone home indicator or floating Checkout. Do not turn disabled marketing containers into focusable elements.

## 3. D4 Shipping contract
- Source: real Woo Store API `cart.shipping_rates`, `cart.totals` and selected rate. No static threshold, code that guesses free shipping from subtotal, or requests to Woo Checkout.
- No rates => neutral “Shipping options depend on your delivery address.”
- A Woo-returned free_shipping rate **selected** => “Free shipping applied”.
- A Woo-returned free_shipping rate **available but not selected** => “Free shipping available — select Free Shipping to apply it.”
- Coupon or address changes must refresh cart from Woo. The Shipping/Payment checkout page remains the authoritative final selection.

## 4. D5 Free Gift gate
- Keep `giftEnabled=false` strict fail closed; no free-gift SKU, eligibility claim, cart line, refund interaction or automatic gift behavior.
- If a future gift workflow is approved, it requires SKU, variant, zones, stacking, stock and Checkout server validation — independent Owner approval.

## 5. D6 recommendations
- First-party Woo recently viewed (distinct/eligible) precedes 30-day eligible completed/processing paid-unit sales. No lifetime popular ranking.
- Where fewer than three true sales candidates exist, fill from eligible catalog picks without claiming they are actual 30-day best sellers.
- Ineligible/out-of-stock/virtual items excluded. Variable products go to View options rather than adding a made-up variant.
- If a recommended product is already in the cart, its Add to cart control stays disabled. No third-party personalization or browser tracking beyond the existing Woo cookie.

## 6. D7 Checkout and /cart/ dual-entry
- Main UX: Cart Drawer V2. Legacy `/cart/` remains directly accessible as Woo-compatible failover. Both use Woo as a single cart data source.
- Checkout CTA is a normal HTTPS GET `wc_get_checkout_url()` link. **No Checkout POST, order creation, payment method switching, Woo settings changes, or checkout migration**.
- Confirm GET navigation /cart/ and /checkout/ routes, same-session amounts, cart mutation parity, coupon messages and cart contents in bounded Dev UAT (no order submission).

## 7. Acceptance and rollout
- Foundation CI, Theme OS and Cart mock PHP/JS contract tests PASS on exact SHA; fail on visible marketing despite OFF flag, fake threshold, checkout POST, wrong mobile scroll/focus behavior, and /cart/ takeover.
- Dev-only file SHA + G2 isolation + verified off-webroot backup + rollback then independent Guest Chrome PC1440/M390 and physical iPhone Safari Owner UAT. Test non-empty, empty, no shipping rates and Woo-eligible shipping rate states, recent-view swap, a failed coupon, and Checkout GET without Place Order.
- PR merge is separately authorized; WWW release is yet another explicit gate. Do not change READY device process state.
