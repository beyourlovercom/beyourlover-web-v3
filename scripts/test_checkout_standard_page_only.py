#!/usr/bin/env python3
"""Checkout Canonical Runtime Guard (source-only; no sites, orders or gateway calls)."""
from pathlib import Path
import re, sys
root=Path(__file__).resolve().parents[1]/"theme"/"beyourlover-v3"
if not root.is_dir():
    raise SystemExit("Missing local theme checkout source")
retired=[
    "assets/css/checkout-drawer.css",
    "assets/css/checkout-visual-v2.css",
    "assets/js/checkout-drawer.js",
    "assets/js/checkout-visual-v2.js",
]
errors=[]
for rel in retired:
    if (root/rel).exists():
        errors.append("retired resource is active: "+rel)
tokens=re.compile(
    r"byl_checkout_drawer|byl-checkout-drawer|BYLCheckoutDrawer|"
    r"checkout-visual-v2|checkout_visual_v2|checkout\.drawer|checkout\.drawer\.mode|"
    r"byl-drawer-checkout",
    re.IGNORECASE,
)
for base in (root/"inc",root/"assets",root/"woocommerce"/"checkout"):
    if not base.exists():
        continue
    for file in base.rglob("*"):
        if not file.is_file() or file.suffix not in (".php",".js",".css",".json"):
            continue
        if tokens.search(file.read_text(encoding="utf-8",errors="replace")):
            errors.append("retired runtime reference: "+str(file.relative_to(root)))
for rel in ("page-checkout.php","theme-os.json"):
    file=root/rel
    if not file.is_file():
        errors.append("missing canonical runtime file: "+rel)
    elif tokens.search(file.read_text(encoding="utf-8",errors="replace")):
        errors.append("retired page/manifest reference: "+rel)
for rel in ("inc/cart-drawer.php","assets/js/cart-drawer.js","assets/css/cart-drawer.css",
            "woocommerce/checkout/form-checkout.php","assets/js/checkout.js"):
    if not (root/rel).is_file():
        errors.append("canonical Cart / Woo Checkout source missing: "+rel)
if errors:
    print("FAIL: Checkout Drawer retirement regression:")
    print("\n".join("- "+e for e in errors))
    sys.exit(1)
print("PASS: no active Checkout Drawer runtime; Cart Drawer and real Woo Checkout sources retained")
