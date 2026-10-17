#!/usr/bin/env python3
from pathlib import Path
r=Path(__file__).resolve().parents[1]/'theme/beyourlover-v3'
js=(r/'assets/js/checkout.js').read_text(encoding='utf-8')
css=(r/'assets/css/checkout.css').read_text(encoding='utf-8')
required=['billing_country_field','billing_first_name_field','billing_last_name_field',
 'billing_address_1_field','billing_address_2_field','billing_city_field',
 'billing_state_field','shipping_phone_field','billing_postcode_field','billing_company_field']
for key in required:
 assert key in js and key in css, key
assert 'function alignShippingAddressFields()' in js
# First-paint UAT added two short-lived native-Woo ordering safeguards:
# initial MutationObserver callback and window load. They reuse the same
# idempotent alignment function, rather than cloning country/state inputs.
assert js.count('    alignShippingAddressFields();')==4
assert 'initialOrderObserver = new MutationObserver(' in js
assert "window.addEventListener('load', () => {" in js
assert 'stopInitialOrderObserver();' in js
assert 'current.some((node, index) => node !== desired[index])' in js
assert 'host.appendChild(node)' in js
assert "node.dataset.bylOptionalFor === 'billing_address_2_field'" in js
assert "node.dataset.bylOptionalFor === 'billing_company_field'" in js
assert "/(?:tax|tin|rfc|vat|cpf|fiscal|customs)/i.test(key)" in js
assert 'Canonical Owner reference — desktop/native field order parity' in css
assert 'grid-template-columns:repeat(2,minmax(0,1fr))!important' in css
assert 'byl_checkout_drawer' not in js
print('PASS Owner reference native field order desktop/mobile: %d fields, no fake controls' % len(required))
