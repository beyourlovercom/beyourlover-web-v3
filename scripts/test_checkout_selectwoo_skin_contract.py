#!/usr/bin/env python3
"""Source-only contract: Checkout SelectWoo popup skin without field or JS rewrites."""
from pathlib import Path
import re

root=Path(__file__).resolve().parents[1]
source=(root/"theme/beyourlover-v3/assets/css/checkout.css").read_text(encoding="utf-8")
script=(root/"theme/beyourlover-v3/assets/js/checkout.js").read_text(encoding="utf-8")
prefix="body.byl-secure-checkout-shell.woocommerce-checkout > .select2-container--open.select2-container--default"
n=0
def ok(value,reason):
    global n
    if not value:
        raise SystemExit("FAIL checkout SelectWoo skin: "+reason)
    n+=1

ok(source.count("Woo SelectWoo dropdown skin") == 1,"exact one scoped style section")
for part in (".select2-dropdown",".select2-search__field",".select2-results__options",
             ".select2-results__option--highlighted","[aria-selected=\"true\"]"):
    ok(prefix+" "+part in source or (prefix+" .select2-results__option"+part) in source,
       "missing scoped popup state: "+part)
ok("background: var(--byl-brand, #99324c) !important;" in source,
   "focus/hover selected menu must use BYL burgundy not WordPress blue")
ok("font: 400 16px/1.35" in source,"iOS search focus must avoid browser autozoom")
ok("min-height: 46px" in source,"mobile option touch target")
ok("overflow-y: auto !important" in source,"native options scrolling retained")
ok("position: fixed" not in source.split("Woo SelectWoo dropdown skin")[-1],
   "do not lock/reparent the dropdown for visual polish")
ok("dropdownParent" not in script and ".select2(" not in script and ".selectWoo(" not in script,
   "do not reinitialize native Woo country/state selectors")
ok("byl_checkout_drawer" not in source and "checkout-drawer" not in source,
   "retired Drawer must remain absent")
ok("body.byl-secure-checkout-shell.woocommerce-checkout > .select2-container--open" in source,
   "popup is outside native Checkout form and scope must include body")
print("PASS SelectWoo native popup brand style contract: %d checks" % n)
