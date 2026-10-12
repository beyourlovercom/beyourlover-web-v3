# Cart Drawer V2 — D1/D2 Owner PASS → D3 Preview Integration

## Design source
Desktop D1 three states and mobile D2 four states approved by Owner.
Mobile standalone visual prototype \`byl_cart_drawer_mobile_v2_preview.html\`
was created for design review; **it is not the Woo storefront runtime**.

## Feature gates
- **Opt-in Dev-only preview**, not a public launch:
  \`https://dev.beyourlover.com/?byl_cart_drawer_preview=1\`
- Gate is checked server-side against \`home_url\` hostname
  \`dev.beyourlover.com\` and query parameter exactly \`1\`.
- Normal visitors, WWW, existing \`/cart/\`, G3 Checkout and G5 payment
  remain unchanged. Header Cart links are intercepted only on the
  explicit Dev preview response. Closing restores focus and scroll.
- Shell in BYL V3 Theme only. Andy Commerce continues to own shipping and
  promotion policy; Woo Store API owns cart items, quantity, coupon and
  amount/session state. No new WordPress plugins or separate data tables.
- **Free Gift is deliberately OFF** (\`giftEnabled=false\`); no fake gift
  or threshold can create business eligibility. The preview shows a
  conspicuous \`gift offers are not active\` label.

## D3.1 acceptance scope
- Desktop: scrim + right-hand two-column Drawer (recommendations/cart);
  mobile: full-width one-column Drawer with a fixed checkout footer,
  recommendations appear below items in the scroll region.
- Empty Cart, real Woo cart lines / product thumbnail / one line total
  per item, remove, debounced quantity updates, coupon entry and removal,
  shipping availability notices from actual Woo shipping rates,
  totals from Woo, native Checkout URL, live product recommendations.
- Cart totals are never recomputed from prices in JS; only Woo-returned
  \`cart.totals\`/selected delivery rates are rendered.
- Keyboard Escape/backdrop to close, focus restoration, no page scroll
  when open, a live status region and actual loading states.
- Product recommendations are simple/in stock/purchasable products via
  Woo; no hard-coded SKU, sales graphic, LELO branding or price.
- To be verified with isolated anonymous Dev Cart session only: empty,
  one-item, multiple-item, quantity, remove, coupon invalid, selected
  Free Shipping where applicable, mobile 390 and desktop 1440.
- No Place Order or Woo Checkout POST; G2 Store API Checkout 403 must
  remain CLOSED. No Woo/ERP sync, inventory/options/DB writes or WWW.

## D3.2 blockers before enabling any actual gift
Owner must define gift SKU/variant, threshold, currency/country/zone,
eligibility subtotal before/after discounts, repeatability, mutually
exclusive offers and budget; specify how the free line is removed on
eligibility loss, inventory rules, cart/checkout reconciliation and
refund/cancellation behavior. Those must be implemented in Andy Commerce
backend after separately approved contract & UAT. A visual \`$0\` frontend
label is NOT a gift award.

## Rollback / merge
Do not deploy without Foundation CI, exact live file-hash preflight,
readable rollback backup, Dev-only URL and independent browser UAT.
No main merge or WWW deploy without respective Owner approval. PR #61
Cart mobile striped-background fix is open independently; do not touch
\`cart-focus.css\` and do not merge it implicitly in this PR.

## D3.2 Owner three-step progress contract

- Global shopping journey: **Cart -> Shipping -> Payment**. The Cart Drawer is always current step Cart; SVG icons are shopping bag, delivery truck, credit card, each accompanied by a visible English label.
- These indicators are accessible, non-interactive status, NOT links or buttons. aria-current="step" denotes the real current stage.
- Current Theme Checkout implementation uses WooCommerce classic checkout with internal address -> delivery -> payment -> review stages. Future G3 visual redesign can map address+delivery to Shipping and payment+review to Payment without changing Woo authoritative checkout, provider rails, security or order processing.
- Checkout visual and runtime changes require a separate design/Owner gate; no early step navigation or fake completion, no Checkout POST in the Cart workstream.
- Dev-only preview, Free Gift OFF, existing Store API cart ownership and G5 protection remain unchanged.

- 2026-10-10 Owner icon follow-up: Cart/Shipping/Payment indicator is presentation-only; isolated Windows and macOS tests of source contract, Woo Cart mocked runtime and PHP syntax passed. Live Dev Owner visual UAT is still pending; Checkout/payment behavior was not changed.
