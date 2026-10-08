import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
theme = (ROOT / "theme/beyourlover-v3/functions.php").read_text(encoding="utf-8")
php = (ROOT / "theme/beyourlover-v3/inc/checkout.php").read_text(encoding="utf-8")
js = (ROOT / "theme/beyourlover-v3/assets/js/checkout.js").read_text(encoding="utf-8")
css = (ROOT / "theme/beyourlover-v3/assets/css/checkout.css").read_text(encoding="utf-8")
docs = (ROOT / "docs/S6-CHECKOUT-PROGRESSIVE-UX.md").read_text(encoding="utf-8")

first_missing_start = js.index("function firstMissing(root)")
first_missing = js[first_missing_start:js.index("function summary", first_missing_start)]
if re.search(r'(?<!input)(?<!select)(?<!textarea)\[aria-required="true"\]', first_missing):
    raise SystemExit("firstMissing must not query generic aria-required nodes")
for marker in ('input[aria-required="true"]', 'select[aria-required="true"]', 'textarea[aria-required="true"]'):
    if marker not in first_missing:
        raise SystemExit(f"firstMissing must include form-control-specific aria-required selector: {marker}")

if "require_once get_stylesheet_directory().'/inc/checkout.php';" not in theme:
    raise SystemExit("Checkout module must be required by functions.php")
checkout_branch = theme[theme.index("if (function_exists('is_checkout')"):]
for marker in ("is_checkout()", "!is_order_received_page()", "assets/css/checkout.css", "assets/js/checkout.js", "apply-coupon", "remove-coupon", "wp_create_nonce('wc_store_api')"):
    if marker not in checkout_branch:
        raise SystemExit(f"Missing checkout-only asset/localization marker: {marker}")
if "woocommerce_ship_to_different_address_checked" not in php or "return byl_v3_checkout_context() ? false" not in php:
    raise SystemExit("Checkout must disable the duplicate shipping address fieldset")
for marker in ("['required'] = true", "Phone number required for delivery", "Your number is used only to help the carrier deliver your order. We do not use it for marketing.", "['autocomplete'] = 'tel'", "['priority'] = 95"):
    if marker not in php:
        raise SystemExit(f"Missing required shipping phone contract marker: {marker}")
for marker in ("woocommerce-shipping-fields", "ship-to-different-address", "woocommerce-form-coupon-toggle", "form.checkout_coupon", "byl-checkout-promotion", "#coupons_list"):
    if marker not in css + js:
        raise SystemExit(f"Missing scoped visibility/slot marker: {marker}")
for marker in ("woocommerce-billing-fields", "woocommerce-account-fields", "woocommerce-additional-fields", "billingFields.contains(accountFields)", "addressBody.appendChild(accountFields)", "addressBody.appendChild(additionalFields)"):
    if marker not in js:
        raise SystemExit(f"Missing native field relocation marker: {marker}")
if "customer.hidden = true" not in js or "customer.setAttribute('aria-hidden', 'true')" not in js:
    raise SystemExit("#customer_details shell must be hidden after native field relocation")
if ".byl-address-section .woocommerce-billing-fields > h3" not in css:
    raise SystemExit("Redundant Billing details heading must be suppressed only inside the BYL address section")
if ".woocommerce-checkout #coupons_list" not in css:
    raise SystemExit("Classic Smart Coupons #coupons_list must be suppressed only on Woo Checkout")
if not (js.index("addressBody.appendChild(billingFields)") < js.index("addressBody.appendChild(accountFields)") < js.index("addressBody.appendChild(additionalFields)")):
    raise SystemExit("Account and additional fields must be appended after the billing/contact fields")
if ".byl-address-section #billing_phone-description" not in css or "display: block" not in css or "padding: 0 !important" not in css or "background: transparent !important" not in css or "color: #666 !important" not in css:
    raise SystemExit("Billing phone privacy microcopy must be visibly styled inside the BYL address section")
for marker in ("#commercekit-timer-wrap", ".wt_coupon_wrapper", ".wt_sc_pagination", "display: none !important"):
    if marker not in css:
        raise SystemExit(f"Checkout must hide legacy giveaway noise: {marker}")
if ".woocommerce-checkout .woocommerce-form-login-toggle" in css:
    raise SystemExit("Returning customer login must remain available")
for marker in (".byl-address-section .woocommerce-billing-fields", ".byl-checkout-summary #order_review", "border-radius: 0 !important", "box-shadow: none !important", "backdrop-filter: none !important"):
    if marker not in css:
        raise SystemExit(f"Missing scoped old-card neutralization marker: {marker}")
if "function setCollapsed(sectionNode, value, text, editable)" not in js:
    raise SystemExit("setCollapsed must accept explicit editable/completed state")
if "setCollapsed(delivery, true, 'Complete Shipping Address first', false)" not in js or "setCollapsed(pay, true, 'Select a delivery method', false)" not in js:
    raise SystemExit("Initial locked Delivery and Payment sections must hide Edit")
if "setCollapsed(address, true, summary(address), true)" not in js or "setCollapsed(delivery, true, selected.nextElementSibling.textContent, true)" not in js:
    raise SystemExit("Completed Address and Delivery sections must show Edit")
if "hidden = !(value && editable)" not in js:
    raise SystemExit("Collapsed Edit visibility must require an explicitly completed section")
if "function syncPhoneDescription()" not in js or js.count("syncPhoneDescription();") < 2:
    raise SystemExit("Billing phone privacy microcopy helper must run during initial enhancement and updated_checkout")
for marker in ("{code: data.applied_code}", "{code: promoForm.querySelector('input').value.trim()}", "config.applyUrl", "config.removeUrl", "updated_checkout", "#payment", "syncShippingRows", "byl-shipping-row-hidden", "dispatchEvent(new Event('change'"):
    if marker not in js:
        raise SystemExit(f"Missing native checkout/promotion integration marker: {marker}")
if '<div class="byl-promo-form">' not in js:
    raise SystemExit("Promo input must use a non-form container inside the outer checkout form")
if '<form class="byl-promo-form">' in js:
    raise SystemExit("Promo must not render a nested form inside the outer checkout form")
if 'button type="button">Apply</button>' not in js or "addEventListener('click'" not in js:
    raise SystemExit("Promo Apply must be a button with a click handler")
if js.count("syncShippingRows();") < 2:
    raise SystemExit("Shipping-row helper must run during initial enhancement and updated_checkout")
if "radio.click()" in js or "radio.dispatchEvent(new Event('change'" not in js:
    raise SystemExit("Delivery proxy must use one native bubbling change path without radio.click double-fire")
if "function syncShippingRows()" not in js or "#order_review .woocommerce-shipping-totals.shipping" not in js:
    raise SystemExit("Shipping-row helper must target every current order-review shipping row")
for marker in ("flex-direction: column", "min-height: 44px", "width: 100%", "CanvasText", "background: Canvas", "@media (min-width: 900px)"):
    if marker not in css:
        raise SystemExit(f"Missing responsive promo contract marker: {marker}")
for marker in ("billing_phone", "shipping_phone", "billing_phone -> shipping_phone", "get_posted_data", "no custom order/post-data writes", "Phone number required for delivery"):
    if marker not in php + docs:
        raise SystemExit(f"Missing native phone-copy rationale marker: {marker}")
if "Add More" in js or "Add More" in css:
    raise SystemExit("Checkout must not render Add More")
if any(marker in css.lower() or marker in js.lower() for marker in ("express-payment", "apple-pay", "google-pay", "payment-request", "express payment")):
    raise SystemExit("Checkout must not hide or hard-code express wallets")
if "appendChild(payment" not in js or "appendChild(replacement)" not in js:
    raise SystemExit("The complete #payment container must be moved/reacquired")
for forbidden in ("wp_insert_post", "wp_update_post", "wp_delete_post", "wc_create_coupon", "update_option(", "add_option(", "wp_create_order", "wc_create_order", "wc_get_order", "wpdb", "INSERT INTO", "UPDATE ", "DELETE FROM"):
    if forbidden in php or forbidden in js:
        raise SystemExit(f"S6 theme files must not perform business/database writes: {forbidden}")
if "Graceful fallback" not in docs or "one BYL promotion slot" not in docs:
    raise SystemExit("S6 documentation must describe ownership and fallback")
print("Checkout progressive UX contract check PASS")
