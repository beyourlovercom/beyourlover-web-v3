# S6 Checkout Progressive UX

Status: local, first-party theme enhancement for the classic WooCommerce Checkout.

S6 keeps one real `/checkout/` form and one shipping fulfillment path. The theme changes presentation only: WooCommerce still owns checkout fields, native validation, shipping-rate calculation, taxes, gateways, terms/privacy, submission, and order creation.

## Frozen ownership

- `theme/beyourlover-v3/inc/checkout.php` disables the duplicate shipping-address fieldset for normal Checkout and makes `billing_phone` the required delivery phone. It is presented only as the delivery phone; there is no separate Billing Phone concept. WooCommerce's native `WC_Checkout::get_posted_data()` behavior maps `billing_phone` -> `shipping_phone` when the shipping fieldset is skipped; no custom order/post-data writes are used.
- `checkout.js` progressively groups the existing billing fields as Shipping Address, then keeps Woo's existing account fields and optional order notes in that same section after the address/contact fields. It hides the now-empty `#customer_details` shell, proxies the existing shipping radios as Delivery Method, moves the complete `#payment` container as Payment, and leaves the native Review/place-order controls in that container. Native inputs, names/IDs, and validation are preserved; an account node already contained by the billing fields is not moved twice.
- The redundant Woo `Billing details` heading inside the Shipping Address section is hidden so the progressive terminology remains consistent. Classic Smart Coupons' `#coupons_list` is suppressed along with the other classic coupon chooser selectors, leaving one BYL promotion slot.
- The original shipping table row stays in `#order_review`; it is visually suppressed while the proxy drives its real radio. A small resync helper marks every current shipping row during initial enhancement and on every Woo `updated_checkout`, because Woo replaces those rows. The proxy uses one bubbling native `change` activation so Woo recalculates once.
- The desktop summary contains the original review heading/table and exactly one BYL promotion slot. The summary returns to normal document flow on mobile.
- Promo apply/remove uses WooCommerce Store API `cart/apply-coupon` and `cart/remove-coupon` with `{code}`. S4 remains the one-slot server enforcement boundary. Automatic free shipping does not use the promo slot.

## Graceful fallback

If JavaScript, DOM assumptions, or the progressive layer fail, the unmodified Woo classic Checkout remains available in the page. The enhancement does not replace payment gateways, create payment controls, move individual gateway children, recalculate rates, validate orders, or create orders. Express/wallet capability UI inside `#payment` is not hidden or hard-coded.

The local test session may show WooCommerce's native "no available payment methods" message; S6 does not invent a gateway.

The delivery phone label remains `Phone number required for delivery`, with the privacy copy visibly shown in S6: `Your number is used only to help the carrier deliver your order. We do not use it for marketing.`
