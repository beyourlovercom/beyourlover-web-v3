#!/usr/bin/env python3
"""Checkout first-paint Woo field order: no dependence on update_order_review."""
from pathlib import Path
js=(Path(__file__).resolve().parents[1]/"theme/beyourlover-v3/assets/js/checkout.js").read_text(encoding="utf-8")
n=0
def check(test,message):
 global n
 if not test:raise SystemExit("FAIL: "+message)
 n+=1
check("function watchInitialFieldOrder()" in js,"short-lived observer startup")
check("initialOrderObserver = new MutationObserver(" in js,"native mutation observer")
check("initialOrderObserver.observe(host, {childList:true})" in js,"observe direct child list only")
check("alignShippingAddressFields();" in js,"align native Woo DOM fields")
check("const host = document.getElementById('billing_first_name_field')?.parentElement" in js,"real Woo host")
check("function stopInitialOrderObserver()" in js,"observer cleanup")
check("window.addEventListener('load', () => {" in js,"load cleanup")
check("stopInitialOrderObserver();" in js,"cleanup called")
check("root.classList.add('is-enhanced');" in js,"native checkout bootstrap")
check("setStep('shipping', {scroll:false});\n  watchInitialFieldOrder();" in js,"watch established after first synchronous boot")
check("$(document.body).on('updated_checkout', () => {\n    // The initial Woo review AJAX" in js,"later AJAX still handled")
check(js.count("stopInitialOrderObserver();") == 2,"cleanup once at AJAX and once at load")
check("host.appendChild(node)" in js,"same original Woo fields")
check("current.some((node, index) => node !== desired[index])" in js,"idempotent sort guard")
check("checkout-drawer.js" not in js and "byl_checkout_drawer" not in js,"no retired drawer")
check("setInterval(" not in js,"no polling side effect")
print("PASS Woo first-paint ordering contract: %d assertions" % n)
