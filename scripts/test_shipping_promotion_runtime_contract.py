from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
policy = (ROOT / "platform/byl-platform/includes/class-byl-shipping-promotion-policy.php").read_text(encoding="utf-8")
runtime = (ROOT / "platform/byl-platform/includes/class-byl-shipping-promotion-runtime.php").read_text(encoding="utf-8")
plugin = (ROOT / "platform/byl-platform/byl-platform.php").read_text(encoding="utf-8")
contract = (ROOT / "docs/C3-CHECKOUT-SHIPPING-PROMOTION-IMPLEMENTATION-CONTRACT.md").read_text(encoding="utf-8")

for marker in (
    "public static function has_persisted_settings(): bool",
    "schema_version'] ?? 0) !== self::SCHEMA_VERSION",
    "public static function is_runtime_active(): bool { return self::has_persisted_settings(); }",
    "eligibility_basis'] ?? null) !== self::ELIGIBILITY_BASIS",
    "zone_overrides",
):
    if marker not in policy:
        raise SystemExit(f"Missing persisted-runtime gate marker: {marker}")

for marker in (
    "woocommerce_shipping_free_shipping_is_available",
    "get_zone_matching_package((array) $package)",
    "get_displayed_subtotal",
    "wc_get_price_decimals",
    "get_applied_coupons",
    "woocommerce_get_shop_coupon_data",
    "'discount_type' => 'fixed_cart'",
    "'amount' => 0",
    "'virtual' => true",
    "'free_shipping' => false",
    "woocommerce_coupon_get_individual_use",
    "woocommerce_apply_with_individual_use_coupon",
    "allow_store_api_individual_use_coupon",
    "woocommerce_cart_loaded_from_session",
    "array_slice($applied, 1)",
    "remove_coupon($code)",
):
    if marker not in runtime:
        raise SystemExit(f"Missing runtime contract marker: {marker}")

if "BYL_Shipping_Promotion_Runtime::boot();" not in plugin:
    raise SystemExit("Shipping promotion runtime is not bootstrapped")

if "add_filter('woocommerce_apply_with_individual_use_coupon', [self::class, 'allow_store_api_individual_use_coupon'], 10, 4);" not in runtime:
    raise SystemExit("Store API compatibility filter must register with four arguments")

store_api_method = runtime[runtime.index("public static function allow_store_api_individual_use_coupon"):runtime.index("    public static function normalize_stale_coupons")]
for marker in (
    "($allow, $coupon, $existing_individual_use_coupons, $cart)",
    "BYL_Shipping_Promotion_Policy::is_runtime_active() && self::is_customer_context() ? true : $allow",
):
    if marker not in store_api_method:
        raise SystemExit(f"Store API compatibility method is missing: {marker}")
if "remove_coupon" in store_api_method or "apply_coupon" in store_api_method:
    raise SystemExit("Store API compatibility must preserve native replacement semantics")

for forbidden in (
    "update_option('woocommerce_",
    'update_option("woocommerce_',
    "add_option('woocommerce_",
    'add_option("woocommerce_',
    "wp_insert_post",
    "wp_update_post",
):
    if forbidden in runtime:
        raise SystemExit(f"Runtime must not write/create Woo coupons or settings: {forbidden}")

for marker in (
    "woocommerce_coupon_get_individual_use",
    "Core `WC_Cart::apply_coupon` validates the candidate before candidate individual-use handling",
    "woocommerce_apply_with_individual_use_coupon",
    "four-argument",
    "validates the candidate first, then checks existing individual-use coupons",
    "native replacement path",
    "does not implement custom remove-before-validate logic",
    "woocommerce_cart_loaded_from_session",
    "retains the first applied coupon",
):
    if marker not in contract:
        raise SystemExit(f"C3 is missing verified one-slot behavior: {marker}")

print("Shipping promotion runtime contract check PASS")
