# Cart — Woo native shipping zone and promo message parity
Date: 2026-10-09. Scope: Dev-only / theme-only, code PR, no checkout or payments.

## Owner screenshot regression
Cart shows subtotal $59, Iowa (US-IA 51503), flat rate $10 and total $69,
while a separate BYL panel incorrectly says "Free shipping unlocked".
Woo shipping zone read-only audit confirmed Iowa falls under Zone 5,
named "1. Zone 1 - $10/ $69 for Free": free_shipping instance 9 requires
$69 minimum; flat_rate instance 10 costs $10. Expected current result:
"Free shipping on orders of $69.00" and "Add $10.00 more for free shipping".
Do NOT rewrite Woo shipping zones or the $49 global BYL policy.

## Root cause
V3 Cart uses the classic `[woocommerce_cart]` runtime, including Woo shipping
address, session, selected shipping method and calculated rates.
Its earlier promotional widget instead read `wcBlocksData.cartStore`, a
separate Store API snapshot that can have a different/shallow destination.
It showed a different zone's "unlocked" state than the actual $10 Woo rate.

## Fix
- The classic Woo Cart embeds read-only JSON promo context inside its own
  cart totals, calculated from Woo's current shipping package and matched zone,
  the Andy Commerce persisted policy, enabled methods, calculated free rates
  and the customer's currently selected rate.
- Cart widget uses that native context **only** on classic Cart; it never
  falls back to a stale Blocks Store API snapshot on this page.
- Always show the effective positive threshold in the current destination,
  below-threshold amount remaining and progress.
- Do not claim shipping is applied when the user selected a paid delivery method;
  show that free shipping is available for selection, or unavailable if the
  relevant Woo rate is absent.
- After native Woo coupon updates, refresh the classic cart totals; preserve
  Woo native coupon fallback if enhanced state is missing.
- No plugin version change, Woo shipping-setting write or payment/order action.

## Nontransactional UAT
1. Dev classic Cart: US-IA 51503, subtotal $59, expect zone $69, $10 remaining,
   flat rate $10, total $69, never "unlocked".
2. Increase cart subtotal past $69 using tester-selected existing products:
   expect free rate available; "applied" only when free method is selected.
3. Switch address to a zero-minimum zone: match Woo actual selected method.
4. Switch address to a zone without a free method: never promise free shipping.
5. Verify classic coupon input and single-code fallback, Checkout unchanged,
   desktop and mobile narrow viewport; Dev Store API Checkout remains 403.
No Place Order, synthetic orders, outbound communication, migrations, or WWW deploy.

## Additional runtime hardening

During Dev read-only WP CLI check, directly calling
`WC_Shipping_Zone::get_shipping_methods(true)` failed after the zone/threshold
checks, while `WC_Shipping_Zones::get_zones()` completed and returned the
correct enabled free/flat methods. The three theme files were immediately
restored from the verified predeploy archive. Updated native context reads
free-service support from Woo's normal zone catalog (and can recognize an
actual zero-cost free rate) without directly rehydrating zone methods.
No shipping settings or orders were modified.

## Owner copy/layout UAT correction (2026-10-10)
The free-shipping promotion card must omit the redundant standalone
"Free shipping" heading. On Iowa Zone 5 at $59, render exactly:
- `Free shipping on orders over $69` (whole-dollar threshold, no decimals)
- `Add $10.00 more for free shipping`
- Progress bar immediately below the remaining amount, then the coupon form.

Use "over" as the Owner-approved customer-facing copy; eligibility calculation
remains WooCommerce's unchanged effective zone rule (minimum $69). Fractional
future thresholds retain cents to avoid silently misrepresenting the amount.

## Final Owner-approved success messaging (2026-10-09)
The cart uses the same Free Shipping V2 victory line as /free-shipping/:
`🎉 Congrats! You've unlocked free shipping.`.
**Eligible but paid service selected:** show celebration **and**
`Select Free Shipping under Shipping above to apply it to your order.`.
**Free delivery selected:** show celebration **and**
`Free shipping applied to your order.`.
Do not celebrate under threshold, when a required code has not been applied,
or when no actual free rate is available. Preserve $69 threshold,
$10.00 remaining, native Woo shipping authority, and anti-stale Blocks guard.
The payment G2 gate remains closed.
