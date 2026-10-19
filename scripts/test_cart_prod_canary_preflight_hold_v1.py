#!/usr/bin/env python3
"""Source-only Window B audit: current Cart is NOT authorized for WWW Canary.

This guard intentionally asserts the current Dev-only/uncoupled release blockers.
If a later, separately authorized PR creates production cohort gates, replace
these HOLD assertions with positive tests of independent server-side opt-ins,
cache isolation, and real legacy fallback. Never treat this PASS as WWW GO.
"""
from pathlib import Path

BASE = Path(__file__).resolve().parents[1]
THEME = BASE / "theme" / "beyourlover-v3"
DOC = BASE / "docs" / "cart" / "CART-CHECKOUT-PRODUCTION-CANARY-V1-WINDOW-B-PREFLIGHT-20261019.md"
checks = 0


def check(condition: bool, reason: str) -> None:
    global checks
    if not condition:
        raise AssertionError("Canary preflight source HOLD violated: " + reason)
    checks += 1


def read(path: str) -> str:
    return (THEME / path).read_text(encoding="utf-8")


drawer = read("inc/cart-drawer.php")
bridge = read("inc/notification-cart.php")
page = read("page-cart.php")
assets = read("inc/theme-os-assets.php")
drawer_js = read("assets/js/cart-drawer.js")
manifest = read("theme-os.json")
doc = DOC.read_text(encoding="utf-8")

# These source-level expectations prove that no safe WWW release contract
# has yet been implemented. One MUST NOT bypass this by replacing "dev"
# with "www" in the same broad gate.
check("function byl_v3_cart_drawer_runtime_enabled(): bool" in drawer, "Drawer runtime gate exists")
check("$host === 'dev.beyourlover.com'" in drawer, "Drawer still restricted to Dev")
check("wp_get_environment_type() === 'staging'" in drawer, "Drawer still restricted to staging")
check("function byl_v3_cart_drawer_preview_enabled(): bool" in drawer, "Dev query preview has a separate gate")
check("if (!byl_v3_cart_drawer_runtime_enabled()) { return; }" in drawer,
      "All-page script/output respects current runtime gate")
check("wp_enqueue_script('byl-v3-cart-drawer'" in drawer, "Drawer assets are runtime gated")
check("data-byl-cart-drawer" in drawer and "data-drawer-checkout" in drawer,
      "Drawer root and native checkout link remain defined")
check("function byl_v3_cart_empty_page_parity_enabled(): bool" in bridge,
      "Empty page parity source exists")
check("&& byl_v3_cart_drawer_runtime_enabled()" in bridge,
      "Empty cart parity is STILL coupled to Drawer runtime; decouple before Canary")
check("woocommerce_locate_template" in bridge and "cart/cart-empty.php" in bridge,
      "Empty cart currently overrides the Woo template when Dev gated")
check("remove_action('woocommerce_cart_collaterals', 'woocommerce_cross_sell_display', 10)" in bridge,
      "Duplicate cart recommendation mitigation remains scoped")
check("do_shortcode('[woocommerce_cart]')" in page,
      "Focused /cart page retains Woo's native cart authority")
check("get_header('cart')" in page and "get_footer('cart')" in page,
      "Focused /cart relies on active-theme template/routing")
check("if (function_exists('is_cart') && is_cart())" in assets,
      "Page assets are still enqueued directly for is_cart (not a production staff gate)")
check("document.addEventListener('click'" in drawer_js and "a.byl-shell-cart" in drawer_js,
      "Drawer JS interposes on native cart navigation if loaded")
check("wp_localize_script('byl-v3-cart-drawer'" in drawer and "'cartApi'" in drawer,
      "Woo Store API endpoint lives in server-localized Drawer config")
check('"inc/cart-drawer.php"' in manifest and '"page-cart.php"' in manifest,
      "Both Cart components are present in the canonical module manifest")

for word in (
    "#145", "#146", "#87", "WINDOW", "default-OFF",
    "cache", "Woo", "kill switches", "NO-GO", "Dev",
):
    # Window heading uses 'Window' capitalization; compare case-insensitively.
    check(word.lower() in doc.lower(), "Release contract is missing " + word)

check("**UNKNOWN / UNVERIFIED**" in doc, "WWW deployed runtime must not be asserted")
check("**UNKNOWN / NOT HASH-VERIFIED on this pass**" in doc,
      "Dev runtime hash is unverified while read-only access is blocked")
print(f"PASS source-only preflight: {checks} assertions. PRODUCTION_CANARY=HOLD; no WWW requests or writes.")
