#!/usr/bin/env python3
"""No fake rates: derive informational locked Free only from actual Woo zone."""
from pathlib import Path

root = Path(__file__).resolve().parents[1] / "theme/beyourlover-v3"
php = (root / "inc/checkout.php").read_text(encoding="utf-8")
js = (root / "assets/js/checkout.js").read_text(encoding="utf-8")
css = (root / "assets/css/checkout.css").read_text(encoding="utf-8")
checks = 0

def check(condition, reason):
    global checks
    if not condition:
        raise SystemExit("FAIL: " + reason)
    checks += 1

for value in (
    "woocommerce_review_order_after_shipping",
    "WC()->shipping()->get_packages()",
    "WC_Shipping_Zones::get_zone_matching_package($package)",
    "$method->id !== 'free_shipping'",
    "$method->get_option('requires', '') !== 'min_amount'",
    "$method->get_option('min_amount', '0')",
    "data-byl-package-index",
    "data-byl-free-threshold",
    "if ($free_available)",
    "screen-reader-text",
):
    check(value in php, "missing real Woo PHP contract: " + value)

for value in (
    "const match = /^shipping_method\\[(\\d+)\\]$/.exec(packageKey)",
    "data-byl-package-index",
    "dataset.bylPackageIndex === match[1]",
    "lockedSource.dataset.bylFreeThreshold",
    "String(input.value || '').startsWith('free_shipping:')",
    "if (threshold && !freeExists)",
    "locked.className = 'byl-delivery-rate is-locked'",
    "locked.setAttribute('aria-disabled', 'true')",
    "locked.setAttribute('role', 'note')",
    "explanation.textContent = 'Available from ' + threshold + ' eligible subtotal'",
):
    if value == "data-byl-package-index":
        check("byl-locked-free-rate-source" in js, "missing Woo rendered selector")
    else:
        check(value in js, "missing disabled card client contract: " + value)
for value in (
    ".byl-delivery-rate.is-locked",
    ".byl-delivery-eligibility",
    "cursor:default!important",
    "border:1px dashed",
):
    check(value in css, "missing informative styling: " + value)

check("dispatchEvent" not in js[js.index("const match = /^shipping_method"):js.index("deliveryHost.appendChild(pack)", js.index("const match = /^shipping_method"))],
      "locked card must never set shipping method")
check("byl_checkout_drawer" not in php and "byl_checkout_drawer" not in js, "no Checkout Drawer comeback")
print("PASS real Woo free eligibility card: %s contract assertions, no transactions" % checks)
