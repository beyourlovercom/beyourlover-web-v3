"""Native Woo country/state interaction P0 static guard."""
from pathlib import Path
import re
root=Path(__file__).resolve().parents[1]
theme=root/"theme/beyourlover-v3"
css=(theme/"assets/css/checkout-drawer.css").read_text()
base=(theme/"assets/css/checkout-drawer.css").read_text()
js=(theme/"assets/js/checkout-drawer.js").read_text()
form=(theme/"woocommerce/checkout/form-checkout.php").read_text()
page=(theme/"page-checkout.php").read_text()
fields=(theme/"inc/checkout.php").read_text()
assets=(theme/"inc/theme-os-assets.php").read_text()
assert re.search(r"\.byl-checkout-drawer-root\s*\{[^}]*z-index:\s*1200",base)
match=re.search(r"body\.byl-checkout-drawer-mode\s*>\s*\.select2-container\.select2-container--open\s*\{\s*z-index:\s*(\d+)\s*!important;?\s*\}",css)
assert match,"Missing native Woo body-appended SelectWoo popup stacking"
assert 1200 < int(match.group(1)) < 2000
assert "woocommerce_checkout_billing" in form
assert "[woocommerce_checkout]" in page
assert "\"billing_country\"" not in js
assert "selectWoo(" not in js
assert "selectWoo(" not in (theme/"assets/js/checkout-visual-v2.js").read_text()
assert "\"\u0027billing_country\u0027 => 5\"" not in ""
assert "billing_country" in fields and "billing_postcode" in fields
assert "byl_v3_checkout_drawer_enabled()" in assets
assert "select2-container--open" not in (theme/"assets/css/checkout.css").read_text()
print("WOO_NATIVE_COUNTRY_STATE_STACKING_SOURCE_GATE_PASS")
