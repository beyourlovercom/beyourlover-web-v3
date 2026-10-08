# S5 Cart Promotion UX

Status: local implementation contract for the WooCommerce Cart (Block or Classic) promotion slot.

S5 adds a read-only `byl-shipping-promotion` extension to the Store API `cart` response. The extension is derived from the active BYL policy, WooCommerce's matching shipping package/zone, and the displayed pre-discount merchandise subtotal. It does not write options, page content, coupons, sessions, WooCommerce settings, or order data.

The extension schema is a direct property map (WooCommerce wraps it as the extension object): `active`, `mode`, `policy_code`, `zone_id`, `threshold`, `subtotal`, `remaining`, `eligible`, `unlocked`, `applied_code`, `has_code`, `policy_code_applied`, and `state`. `state` uses the C2 values `inactive`, `below_threshold`, `automatic_unlocked`, `automatic_code`, `require_code_available`, `require_code_other`, `require_code_applied`, and `require_code_unlocked`. The remove-coupon request includes the required `{code: applied_code}` field.

The theme loads `cart.css` and `cart.js` only on `is_cart()`. The browser reads `cart.extensions['byl-shipping-promotion']`, renders one lightweight promotion slot in the Cart totals area, and calls WooCommerce's native `cart/apply-coupon` and `cart/remove-coupon` routes. Server-side S4 remains the one-slot enforcement boundary.

The visible states follow C2 exactly:

- Below threshold: `Add <remaining> more for free shipping` plus a thin progress meter.
- Automatic unlock: `Free shipping unlocked`.
- Require-code threshold: `Free shipping available` and `Use code BYL49` (using the configured policy code).
- Require-code with another code: `Replace your current code with BYL49 to unlock free shipping.`
- Applied code: `<CODE> applied` and one `Edit` action. Edit reveals the same single input and a small `Remove` action; there is no `Add More` action.

The core Cart coupon form is hidden only within the Cart (Block or Classic) UI, and only Smart Coupons' available-coupons chooser is hidden when present. The Cart Express Payment block is not hidden. Checkout remains S6 and is out of scope.

No production, page-content, WooCommerce settings, coupon, order, or deployment action is authorized by this document.


## Dev compatibility note
The single BYL promotion slot supports both Woo Cart Blocks and Classic Cart. On Classic Cart it mounts inside .cart_totals before the native checkout CTA, while the native Classic coupon form and Smart Coupons chooser remain hidden.
