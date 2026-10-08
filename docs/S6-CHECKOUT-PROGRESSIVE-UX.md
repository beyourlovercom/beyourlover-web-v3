# S6 Checkout Progressive UX — Dev Integration

Status: Local PASS behavior integrated onto the latest main Checkout V2 architecture for Dev validation.

## Ownership

Latest main Checkout V2 is authoritative for the Checkout structure and interaction model:

- Shipping Address → Delivery Method → Payment → Review on one /checkout/ URL.
- Compact completed summaries with Edit.
- Native Woo shipping rates, totals, validation, payment container, terms, nonce, and order submission remain authoritative.
- Mobile keeps the accepted sticky primary CTA and safe-area behavior.
- shipping_phone remains the single required delivery-contact phone used by latest main. Its visible privacy copy is: Your number is used only to help the carrier deliver your order. We do not use it for marketing.

This integration does not reintroduce the older Local S6 DOM-rebuild architecture.

## Shipping promotion integration

- The existing Checkout V2 Order Summary owns exactly one BYL promotion slot through data-byl-promotion.
- Promo apply/remove uses Woo Store API cart/apply-coupon and cart/remove-coupon with {code}.
- S4 backend individual-use enforcement remains the one-slot authority.
- Automatic free shipping does not consume the promo slot.
- Native Woo coupon form and Smart Coupons chooser/classic #coupons_list are suppressed only on Checkout to prevent duplicate promo entry points.
- The complete native #payment container is preserved; gateway and wallet capability UI are neither recreated nor hidden.
- The promotion layer is presentation-only and performs no option, coupon, order, or database writes.

## Graceful fallback

If shipping-promotion presentation data cannot load, Woo Checkout remains the transaction authority. No fake payment method, wallet, shipping rate, order success, or gateway result is created.

## Dev gate

Dev validation must prove the accepted Checkout V2 flow still works and that the one BYL promotion slot behaves correctly for Apply → Edit → Remove, automatic free shipping, required-code mode, representative shipping zones, mobile width, and real Dev payment availability without submitting a fabricated order.
