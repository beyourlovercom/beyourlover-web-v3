#!/usr/bin/env python3
"""Shipping vs billing and account-save UI guards; source only."""
from pathlib import Path
root=Path(__file__).resolve().parents[1]/"theme/beyourlover-v3"
php=(root/"inc/checkout-address-contract.php").read_text()
include=(root/"inc/checkout.php").read_text()
form=(root/"woocommerce/checkout/form-checkout.php").read_text()
js=(root/"assets/js/checkout.js").read_text()
css=(root/"assets/css/checkout.css").read_text()
n=0
def check(v,label):
 global n
 if not v:raise AssertionError(label)
 n+=1
for key in ("woocommerce_checkout_posted_data","woocommerce_after_checkout_validation",
 "woocommerce_checkout_create_order","woocommerce_checkout_update_customer_data",
 "woocommerce_checkout_update_customer","woocommerce_checkout_get_value"):
 check(key in php,key)
for frag in ("is_user_logged_in() && $save === '1'",
 "return (bool) $allowed && is_user_logged_in() && $consent === '1'",
 "byl_v3_address_from_checkout($data, 'billing_')",
 "byl_v3_address_from_checkout($data, 'byl_invoice_')",
 "byl_v3_apply_wc_address($order, 'shipping'",
 "byl_v3_apply_wc_address($order, 'billing'",
 "byl_v3_apply_wc_address($customer, 'shipping'",
 "byl_v3_apply_wc_address($customer, 'billing'",
 "WC_Validation::is_postcode","get_allowed_countries","get_states",
 "get_shipping_' . $m[1]"):
 check(frag in php,frag)
check("require_once __DIR__ . '/checkout-address-contract.php';" in include,"native contract loaded")
for frag in ('name="byl_save_address"', 'disabled(!is_user_logged_in())',
  'name="byl_use_shipping_for_billing" value="0"',
  'name="byl_use_shipping_for_billing" value="1" checked',
  'data-byl-invoice-address hidden','data-byl-invoice-fields disabled',
  "woocommerce_form_field('byl_invoice_' . $key", "new WC_Customer(get_current_user_id())",
  "Log in to save an address for future orders."):
 check(frag in form,frag)
check("data-byl-invoice-fields" in js and "syncBillingAddressChoice()" in js,"JS conditional native address")
check("fields.disabled = !separate" in js,"unselected invoice fields not posted")
check("validateAddress()" in js and "Please complete the separate billing address." in js,"real validation before Payment")
check("review('billing'" in js,"show true billing summary")
for token in ('wc_address_i18n_params', "['state','postcode']", "spec.hidden === true",
              "spec.required !== false", "input.disabled = hidden", "syncInvoiceLocale()"):
 check(token in js, "Country locale UI contract: " + token)
check("get_address_fields($country, 'billing_')" in php,"actual Woo locale validation, not one country fits all")
check(".byl-invoice-address[hidden]" in css and ".byl-address-choice input[type=checkbox]:checked" in css,"burgundy visual")
check("checkout-drawer.js" not in js and "byl_checkout_drawer" not in form,"no retired checkout drawer")
print("PASS Checkout genuine address source contract: %s checks" % n)
