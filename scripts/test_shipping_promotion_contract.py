from pathlib import Path

source = Path("platform/byl-platform/includes/class-byl-shipping-promotion-policy.php").read_text(encoding="utf-8")
plugin = Path("platform/byl-platform/byl-platform.php").read_text(encoding="utf-8")

required = [
    "OPTION_NAME = 'byl_shipping_promotion_settings'",
    "mode' => 'automatic'",
    "free_shipping_code' => 'BYL49'",
    "global_threshold' => 49.00",
    "ELIGIBILITY_BASIS = 'pre_discount_merchandise_subtotal'",
    "Rest of world",
    "manage_woocommerce",
    "register_setting",
    "add_filter('option_page_capability_' . self::SETTINGS_GROUP",
    "return 'manage_woocommerce';",
    "WooCommerce free-shipping method settings are never written",
    "code.disabled=!requireCode",
    "byl-code-warning-callout",
    "style.setProperty(\"display\",requireCode?\"block\":\"none\",\"important\")",
]
for marker in required:
    if marker not in source:
        raise SystemExit(f"Missing shipping policy contract marker: {marker}")

register_block = source[source.index("register_setting("):source.index("    public static function render_platform_page")]
if "'default'" in register_block:
    raise SystemExit("register_setting must not install a default option value")
if "self::defaults(true)" not in source or "self::defaults(!$has_saved_settings)" in source:
    raise SystemExit("Woo-derived defaults must be the base even when settings are saved")
if "config['threshold'] ?? $settings['zone_overrides']" not in source:
    raise SystemExit("Saved zone thresholds must overlay Woo-derived zone defaults")
if "clean_threshold($threshold, float $fallback = 49.00)" not in source:
    raise SystemExit("Threshold sanitizer must preserve a fallback for invalid input")
if "notice notice-warning" in source:
    raise SystemExit("Code warning must not depend on WordPress notice classes")
if "warning.hidden=!requireCode" not in source or "style.setProperty(\"display\",requireCode?\"block\":\"none\",\"important\")" not in source or "warning.setAttribute(\"aria-hidden\",requireCode?\"false\":\"true\")" not in source or "settings['free_shipping_code']" not in source:
    raise SystemExit("Code warning must synchronize hidden, aria-hidden, and display state in require-code mode")
if "class=\"byl-code-warning-callout\"" not in source or "style=\"display:block !important\" aria-hidden=\"false\"" not in source or "hidden style=\"display:none !important\" aria-hidden=\"true\"" not in source:
    raise SystemExit("Code warning must have an explicit server-rendered visibility state")
if "free_shipping_code'] ?? $current['free_shipping_code']" not in source:
    raise SystemExit("Disabled code input must preserve the saved code when omitted from POST")
if "isset($method->id) ? $method->id : ($method->method_id ?? '')" not in source:
    raise SystemExit("Free-shipping detection must support Woo objects exposing public id")
if "if ($method_id !== 'free_shipping') { continue; }" not in source:
    raise SystemExit("Only free_shipping methods may have min_amount inspected")

for forbidden in ("update_option('woocommerce_free_shipping", "update_option(\"woocommerce_free_shipping"):
    if forbidden in source:
        raise SystemExit(f"Shipping policy must not write Woo settings: {forbidden}")

if "class-byl-shipping-promotion-policy.php" not in plugin or "BYL_Shipping_Promotion_Policy::boot();" not in plugin:
    raise SystemExit("Shipping promotion policy is not bootstrapped")

print("Shipping promotion contract check PASS")
