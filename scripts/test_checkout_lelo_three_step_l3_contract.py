"""Source-only safety gate; never starts a Woo order or payment."""
from pathlib import Path
ROOT = Path(__file__).resolve().parents[1]
p = ROOT / "theme/beyourlover-v3"
form = (p / "woocommerce/checkout/form-checkout.php").read_text()
script = (p / "assets/js/checkout.js").read_text()
fields = (p / "inc/checkout.php").read_text()
css = (p / "assets/css/checkout.css").read_text()
page = (p / "page-checkout.php").read_text()
assert "do_shortcode('[woocommerce_checkout]')" in page
assert form.count('<form name="checkout"') == 1
assert "data-needs-shipping" in form
assert "data-needs-payment" in form
for tag in ('cart', 'shipping', 'payment'):
    assert f'data-byl-track="{tag}"' in form, tag
assert form.count('data-byl-step="shipping"') == 1
assert form.count('data-byl-step="payment"') == 1
assert 'data-byl-open-step="address"' not in form
for obsolete in ('data-byl-step="address"', 'data-byl-step="delivery"',
                 'data-byl-step="review"'):
    assert obsolete not in form, obsolete
assert form.index('data-byl-delivery-options') < form.index('id="customer_details"')
assert form.index('data-byl-payment-host') < form.index('data-byl-terms-host')
# Owner: Woo real order notes belong in the optional Shipping accordion, never Payment.
assert form.index('data-byl-notes-host') < form.index('data-byl-step="payment"')
assert form.count('data-byl-notes-host') == 1
assert "wireOptionalDeliveryNote()" in script
assert "data-byl-country-notices hidden" in form
assert "Log in or create an account" in form and "Edit your profile" in form
assert form.index('data-byl-account-email-host') < form.index('data-byl-delivery-options')
assert "function mountWooAccountEmail()" in script
assert "host.appendChild(field)" in script
assert "[data-byl-account-email-host] .validate-required" in script
assert "data-byl-marketing-optin-slot hidden" in form
assert "const steps = ['shipping', 'payment']" in script
assert "nativeButton.click()" in script
assert "review.querySelector('#payment')" in script
assert "if (fresh && mounted && mounted !== fresh) mounted.remove()" in script
assert "if (fresh && oldTerms) oldTerms.remove()" in script
assert "function shippingReady()" in script
assert "function wooShippingGroups()" in script and "function chosenShippingRates()" in script
assert "only.type === 'hidden'" in script
assert "chosenShippingRates().length === wooShippingGroups().size" in script
assert "if (active !== 'payment' || !paymentConfigured()" in script
assert "function wireOptionalAddressFields()" in script
assert "billing_address_2_field" in script and "billing_company_field" in script
assert "trigger.dataset.bylOptionalFor = id" in script
assert "'billing_country' => 5" in fields
assert "'billing_postcode' => 85" in fields
assert "data-byl-payment-express-host" in form
assert "input.checked = true" in script
for forbidden in ("devPrecheck", "syncConsentCheckboxes", "consentChoice",
                  "terms.checked = true", "consentInput.checked = true"):
    assert forbidden not in script, forbidden
for required in ("'priority' => 70", "'label' => 'Phone'",
                 "We will only use this number in case we need to contact you regarding delivery purposes.",
                 "['billing_company']['required'] = false"):
    assert required in fields, required
assert css.count(".byl-checkout-track{") >= 1
assert ".byl-optional-address-trigger[hidden]" in css
assert ".byl-progressive-checkout [data-byl-terms-host]" in css
for bad in ("wc_create_order", "wp_create_user", "process_payment", "update_option("):
    assert bad not in script and bad not in fields, bad
print("LELO_L3_THREE_STEP_CONTRACT_PASS: Woo native checkout, 2 checkout phases + Cart, no fabricated consent/order/mail")
