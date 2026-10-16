"""Checkout Visual Polish V2 — source-only Owner/gateway isolation contract."""
from pathlib import Path
import json
root=Path(__file__).resolve().parents[1]
theme=root/'theme/beyourlover-v3'
assets=(theme/'inc/theme-os-assets.php').read_text()
registry=json.loads((theme/'theme-os.json').read_text())
css=(theme/'assets/css/checkout-visual-v2.css').read_text()
js=(theme/'assets/js/checkout-visual-v2.js').read_text()
assert "wp_enqueue_style('byl-v3-checkout-visual-v2'" in assets
assert "wp_enqueue_script('byl-v3-checkout-visual-v2'" in assets
assert "['byl-v3-checkout-drawer']" in assets
assert "function_exists('byl_v3_checkout_drawer_enabled') && byl_v3_checkout_drawer_enabled()" in assets
assert '"assets/css/checkout-visual-v2.css"' in (theme/'theme-os.json').read_text()
assert '"assets/js/checkout-visual-v2.js"' in (theme/'theme-os.json').read_text()
for marker in (
 'body.admin-bar.byl-checkout-drawer-mode #wpadminbar',
 'body.byl-checkout-drawer-mode .byl-checkout-drawer-close',
 'body.byl-checkout-drawer-mode .byl-checkout-next',
 'body.byl-checkout-drawer-mode .byl-summary-product',
 'body.byl-checkout-drawer-mode [data-byl-payment-express-host]',
 'body.byl-checkout-drawer-mode .byl-account-entry--signed-in',
 '@media(max-width:760px)',
 '@media(max-width:375px)',
):
 assert marker in css,marker
for marker in (
 '[data-byl-checkout-drawer-root]',
 'data-byl-progressive-checkout',
 'data-byl-promotion',
 "root.querySelectorAll('#order_review input[name^=\"shipping_method\"]')",
 'selected delivery',
 'Woo',
 "data-byl-error",
 'data-byl-drawer-payment-total-details',
 'updated_checkout.bylCheckoutVisualV2',
):
 assert marker in js,marker
# Visual layer MUST NOT start a gateway, assume eligibility, alter the cart,
# write Woo session/DB, or touch terms/consent/identity.
for forbidden in (
 'fetch(', 'XMLHttpRequest', 'navigator.sendBeacon',
 'wp_ajax_', 'wc-ajax=', 'wp-json/', "trigger('update_checkout')",
 'place_order', 'submit()', 'requestPayment', 'confirmPayment',
 'setPaymentMethod', 'wp_create_user',
 'shipping_method[0]', 'set_cookie',
):
 assert forbidden not in js,('VISUAL_ONLY_CONTRACT_VIOLATION',forbidden)
assert "isFreeRate" in js
assert "/^free_shipping(?::|$)/" in js
assert "source !== 'Free shipping unlocked'" in js
assert "No payment method is available. Please try again later." in js
assert "paymentHost.querySelectorAll('#payment .woocommerce-info" in js
assert "Never hide gateway errors" in js
assert "event.stopImmediatePropagation()" in js
print("CHECKOUT_VISUAL_V2_SOURCE_GOVERNANCE_PASS: staging Drawer only; no payment/cart writes; Woo totals/rates authoritative")
