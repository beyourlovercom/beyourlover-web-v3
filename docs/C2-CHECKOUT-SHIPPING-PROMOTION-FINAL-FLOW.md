# C2 Checkout / Shipping Promotion Final Flow

Status: canonical, static rendering contract and demo. Documentation/demo only; this file defines no runtime behavior.

## Purpose

C2 is the canonical mobile-first Apple-style Cart / Checkout shipping-promotion rendering. The design starts as a narrow, calm, single-column checkout and expands to a restrained two-column desktop layout. It uses generous whitespace, low visual noise, few borders/cards, and collapsible completed sections that become a summary with `Edit`.

Shipping is the only fulfillment path in C2. Pickup is not rendered. Apple Pay and Google Pay are not rendered on a product page.

## Canonical sequence

The checkout sections appear in this exact order:

1. `Shipping Address`
2. `Delivery Method`
3. `Payment`
4. `Review`
5. `Place Order`

Completed sections collapse to a one-line summary plus `Edit`. The current section stays open. `Place Order` is the final action, not a separate stepper circle or a large promotional interruption.

### Shipping address copy contract

Phone is required. The field is labelled `Phone number` and uses this exact supporting copy:

> **Phone number required for delivery**
> Your number is used only to help the carrier deliver your order. We do not use it for marketing.

The address form does not imply marketing consent. The phone field must not be hidden behind an optional or expandable control.

## Promotion state machine

The promotion is evaluated against the configured free-shipping threshold. The C2 rendering exposes one promotion slot, and that slot remains usable after an automatic threshold unlock. A shopper can never see a second code input for the same order.

| State | Shipping result | Promotion rendering | Allowed transition |
| --- | --- | --- | --- |
| Below threshold | Shipping is not yet free | `Add $10 more for free shipping` with a lightweight progress indicator; one usable promo slot | Cart subtotal reaches threshold → automatic unlock or `Free shipping available` depending on mode; entering a valid code → applied state |
| Automatic threshold reached | `Shipping FREE` | `Free shipping unlocked`; promo slot remains usable | Enter valid `EMMA10` → automatic + EMMA10; Edit → code can be changed/removed per checkout policy |
| Automatic + `EMMA10` | `Shipping FREE` | `EMMA10 applied` and `Edit` only; no Add-more message | `Edit` → return to the one usable promo slot |
| Require-code threshold reached, no code | Free shipping available but not unlocked | `Free shipping available`; `Use code BYL49`; exactly one input slot | Apply `BYL49` → require-code + BYL49; apply another valid code → replace the current code in the same slot |
| Require-code + `EMMA10` | Normal paid shipping remains; it is not unlocked by `EMMA10` | `EMMA10 applied` and `Edit` only; explain: `Replace your current code with BYL49 to unlock free shipping.` No second slot. Render the selected rate and amount once known; otherwise show the normal not-yet-calculated shipping state. Never show `FREE`. | `Edit` → replace `EMMA10` with `BYL49` in the same slot → unlocked |
| Require-code + `BYL49` | `Shipping FREE` | `BYL49 applied` and `Edit` only | `Edit` → return to the one usable promo slot |

### Exact copy

These strings are frozen for the C2 rendering:

- Below threshold: `Add $10 more for free shipping`
- Automatic unlocked: `Free shipping unlocked` and `Shipping FREE`
- Automatic + EMMA10: `EMMA10 applied`, `Edit`, and `Shipping FREE`
- Require-code, no code: `Free shipping available` and `Use code BYL49`
- Require-code + EMMA10: `EMMA10 applied`, `Edit`, `Replace your current code with BYL49 to unlock free shipping.`, and normal paid shipping: render the selected rate and amount once known, otherwise the normal not-yet-calculated state; never `FREE`
- Require-code + BYL49: `Free shipping unlocked`, `BYL49 applied`, `Edit`, and `Shipping FREE`

The automatic threshold state must not show `Add $10 more`. The require-code + EMMA10 state must not show `Shipping FREE` or a second input slot. Every applied-code state uses `Edit` only in the visible promotion row.

## Visual contract

- Mobile is the design origin: one column, generous vertical rhythm, clear section hierarchy, and full-width primary actions.
- Desktop adapts to a narrow checkout column plus a quiet order-summary column; it does not become a dense dashboard.
- Use whitespace as the main grouping device. Prefer hairline separators and soft surfaces over stacked cards.
- Completed sections collapse to summary + `Edit`; do not use large SaaS stepper circles.
- Progress is lightweight: a thin meter and concise copy, not a dominant banner or popup.
- The promo slot is inline and compact. Never introduce a second coupon field.

## Demo boundary

`docs/demos/c2-checkout-shipping-promotion-flow.html` is a self-contained, static visual reference. It intentionally has no external assets, imports, scripts, framework, network link, or runtime integration. The six state panels are documentation fixtures, not interactive checkout behavior.
