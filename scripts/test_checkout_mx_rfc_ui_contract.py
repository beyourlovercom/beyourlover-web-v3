#!/usr/bin/env python3
"""Source-only UI regression: real MX RFC input, conditional and correctly placed."""
from pathlib import Path

r = Path(__file__).resolve().parents[1] / "theme/beyourlover-v3"
inc = (r / "inc/checkout.php").read_text(encoding="utf-8")
php = (r / "inc/checkout-mx-rfc.php").read_text(encoding="utf-8")
js = (r / "assets/js/checkout.js").read_text(encoding="utf-8")
css = (r / "assets/css/checkout.css").read_text(encoding="utf-8")
checks = 0

def verify(condition, name):
    global checks
    if not condition:
        raise SystemExit("FAIL: " + name)
    checks += 1

verify("require_once __DIR__ . '/checkout-mx-rfc.php';" in inc, "active theme includes real PHP contract")
for token in ("woocommerce_checkout_fields", "billing_rfc_tax_id", "'priority' => 95",
              "woocommerce_after_checkout_validation", "woocommerce_checkout_create_order",
              "_billing_rfc_tax_id", "update_meta_data", "billing_country"):
    verify(token in php, "real Woo RFC backend: " + token)
for token in ("function syncMexicoRFCField()", "country.value === 'MX'",
              "taxField.hidden = !enabled", "input.disabled = !enabled",
              "input.required = enabled", "if (!enabled && input.value) input.value = ''",
              "data-byl-country-required", "syncMexicoRFCField();",
              "on('change', '#billing_country', syncMexicoRFCField)"):
    verify(token in js, "client-country contract: " + token)
for token in ("#billing_rfc_tax_id_field:not(.is-country-active)",
              "#billing_rfc_tax_id_field.is-country-active",
              "grid-column:1/-1", "order:10!important", "order:100!important"):
    verify(token in css, "responsive RFC order: " + token)
verify("byl_checkout_drawer" not in inc and "checkout-drawer.js" not in js, "do not revive old checkout drawer")
verify("localStorage" not in js[js.index("function syncMexicoRFCField()"):js.index("$(document).on('change', '#billing_country', syncMexicoRFCField)")], "no client persistence of tax ID")
print("PASS Mexico RFC V1 UI/source contract: %d checks" % checks)
