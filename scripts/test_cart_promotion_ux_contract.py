from pathlib import Path
import re


ROOT = Path(__file__).resolve().parents[1]
runtime = (ROOT / "platform/byl-platform/includes/class-byl-shipping-promotion-runtime.php").read_text(encoding="utf-8")
theme = (ROOT / "theme/beyourlover-v3/functions.php").read_text(encoding="utf-8")
js = (ROOT / "theme/beyourlover-v3/assets/js/cart.js").read_text(encoding="utf-8")
css = (ROOT / "theme/beyourlover-v3/assets/css/cart.css").read_text(encoding="utf-8")
docs = (ROOT / "docs/S5-CART-PROMOTION-UX.md").read_text(encoding="utf-8")

mobile_css = css.split("@media (min-width: 700px)", 1)[0]
if "byl-cart-promotion__form" not in mobile_css or "flex-direction: column" not in mobile_css or "width: 100%" not in mobile_css:
    raise SystemExit("Mobile promotion form must stack as a full-width column")
if not re.search(r"\.woocommerce-cart \.byl-cart-promotion__form input,\s*\.woocommerce-cart \.byl-cart-promotion__form button \{[^}]*min-height:\s*[^;]+;", mobile_css, re.S):
    raise SystemExit("Mobile promotion controls need a comfortable min-height")
if ".byl-cart-promotion__form button { border: 1px solid CanvasText; background: CanvasText; color: Canvas;" not in mobile_css:
    raise SystemExit("Mobile Apply button must retain a clear neutral primary affordance")
desktop_css = css.split("@media (min-width: 700px)", 1)[1]
if "flex-direction: row" not in desktop_css or "max-width: 28rem" not in desktop_css:
    raise SystemExit("Desktop promotion form must remain a horizontal 28rem row")

for marker in (
    "woocommerce_blocks_loaded",
    "woocommerce_store_api_register_endpoint_data",
    "'endpoint' => 'cart'",
    "'namespace' => 'byl-shipping-promotion'",
    "get_cart_presentation_state",
    "get_cart_presentation_schema",
    "get_persisted_zone_effective_threshold",
    "get_displayed_subtotal",
    "'policy_code_applied'",
    "'state'",
    "'require_code_applied'",
    "woocommerce_apply_with_individual_use_coupon",
    "woocommerce_cart_loaded_from_session",
):
    if marker not in runtime:
        raise SystemExit(f"Missing S5 Store API marker: {marker}")

for forbidden in (
    "update_option(", "add_option(", "delete_option(", "wp_insert_post(",
    "wp_update_post(", "wp_delete_post(", "wc_create_coupon(", "wp_delete_post(",
    "wp_set_object_terms(", "wp_insert_post(", "wp_update_post(",
):
    if forbidden in runtime:
        raise SystemExit(f"S5 runtime must remain read-only: {forbidden}")

cart_branch = theme[theme.index("if (function_exists('is_cart')"):]
if "is_cart()" not in cart_branch or "assets/css/cart.css" not in cart_branch or "assets/js/cart.js" not in cart_branch:
    raise SystemExit("Cart assets must be scoped to is_cart()")
if "rest_url('wc/store/v1/cart/apply-coupon')" not in cart_branch or "rest_url('wc/store/v1/cart/remove-coupon')" not in cart_branch:
    raise SystemExit("Native Store API coupon routes must be localized")
if "wp_create_nonce('wc_store_api')" not in cart_branch:
    raise SystemExit("Cart must localize the wc_store_api nonce")
product_start = theme.index("if (function_exists('is_product')")
cart_start = theme.index("if (function_exists('is_cart')", product_start)
product_branch = theme[product_start:cart_start]
if "cart.js" in product_branch or "cart.css" in product_branch:
    raise SystemExit("Cart UI must not be enqueued on product pages")

for marker in (
    "extensions['byl-shipping-promotion']",
    "Free shipping unlocked",
    "Free shipping available",
    "Replace your current code with ",
    "class=\"byl-cart-promotion__edit\"",
    "class=\"byl-cart-promotion__remove\"",
    "receiveCart",
    "applyUrl",
    "removeUrl",
    "mutate(config.removeUrl, {code: data.applied_code}, 'remove')",
    "mutate(config.applyUrl, {code: input ? input.value.trim() : ''}, 'apply')",
):
    if marker not in js:
        raise SystemExit(f"Missing Cart UI marker: {marker}")
if "Add More" in js or "Add More" in css:
    raise SystemExit("S5 must not render Add More")
if ".express" in css.lower() or "express-payment" in css.lower() or "express-payment" in js.lower():
    raise SystemExit("Express Payment must not be hidden or controlled by S5 selectors")
if "wc-block-cart" not in css or "wc-block-components-totals-coupon" not in css:
    raise SystemExit("Core coupon form must be hidden only in Cart UI")
if "wp-data" not in cart_branch or "wc-blocks-data-store" not in cart_branch:
    raise SystemExit("Cart script must depend on wp-data and wc-blocks-data-store")
if "'wc-blocks-data'" in cart_branch:
    raise SystemExit("S5 cart script must not use the stale exact wc-blocks-data dependency")

schema = runtime[runtime.index("public static function get_cart_presentation_schema"):runtime.index("public static function get_cart_presentation_state")]
if "'type' => 'object'" in schema or "'properties' =>" in schema:
    raise SystemExit("Cart Store API schema must be a direct property map")
for field in ("active", "mode", "policy_code", "zone_id", "threshold", "subtotal", "remaining", "eligible", "unlocked", "applied_code", "has_code", "policy_code_applied", "state"):
    if "'" + field + "'" not in schema:
        raise SystemExit(f"Missing Cart Store API schema field: {field}")
presentation = runtime[runtime.index("public static function register_store_api_endpoint_data"):runtime.index("public static function filter_free_shipping")]
if "apply_coupon(" in presentation or "remove_coupon(" in presentation or "update_option(" in presentation or "add_option(" in presentation or "delete_option(" in presentation:
    raise SystemExit("Cart presentation extension must not mutate coupons")
if "if (!$eligible) { $state = ($mode === 'require_code' && $policy_code_applied) ? 'require_code_applied' : 'below_threshold'; }" not in presentation:
    raise SystemExit("Below-threshold state must guard require_code_applied by require_code mode")
if "receiveCart" not in js or "window.location.reload()" not in js:
    raise SystemExit("Cart mutations must use receiveCart with reload fallback")

for marker in ("Add <remaining>", "one lightweight promotion slot", "Checkout remains S6"):
    if marker not in docs:
        raise SystemExit(f"Missing S5 documentation marker: {marker}")

print("Cart promotion UX contract check PASS")
