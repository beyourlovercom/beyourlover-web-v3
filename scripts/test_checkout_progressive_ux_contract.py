from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
theme = (ROOT / "theme/beyourlover-v3/functions.php").read_text(encoding="utf-8")
php = (ROOT / "theme/beyourlover-v3/inc/checkout.php").read_text(encoding="utf-8")
js = (ROOT / "theme/beyourlover-v3/assets/js/checkout.js").read_text(encoding="utf-8")
css = (ROOT / "theme/beyourlover-v3/assets/css/checkout.css").read_text(encoding="utf-8")
form = (ROOT / "theme/beyourlover-v3/woocommerce/checkout/form-checkout.php").read_text(encoding="utf-8")
docs = (ROOT / "docs/S6-CHECKOUT-PROGRESSIVE-UX.md").read_text(encoding="utf-8")

for marker in (
    'data-byl-progressive-checkout',
    'data-byl-step="address"',
    'data-byl-step="delivery"',
    'data-byl-step="payment"',
    'data-byl-step="review"',
    'data-byl-payment-host',
    'data-byl-next',
    'byl-summary-card',
):
    if marker not in form:
        raise SystemExit(f"Missing accepted Checkout V2 template marker: {marker}")

for marker in (
    "const steps = ['address', 'delivery', 'payment', 'review']",
    "function validateAddress()",
    "function deliveryOptions()",
    "function paymentConfigured()",
    "nativeButton.click()",
    "No payment method is available. Please try again later.",
):
    if marker not in js:
        raise SystemExit(f"Missing accepted Checkout V2 behavior marker: {marker}")

for marker in (
    "unset($fields['billing']['billing_phone'])",
    "unset($fields['billing']['billing_company'])",
    "unset($fields['shipping']['shipping_company'])",
    "['shipping_phone']",
    "'required' => true",
    "'label' => 'Shipping phone'",
    "Your number is used only to help the carrier deliver your order. We do not use it for marketing.",
    "'autocomplete' => 'tel'",
):
    if marker not in php:
        raise SystemExit(f"Missing shipping phone contract marker: {marker}")
if "function syncPhoneDescription()" not in js or js.count("syncPhoneDescription();") < 2:
    raise SystemExit("Shipping phone description must be kept visible through initial and updated_checkout states")
if "#shipping_phone-description" not in css or "display:block!important" not in css:
    raise SystemExit("Shipping phone privacy description must be visibly styled")

if 'data-byl-promotion' not in form:
    raise SystemExit("Checkout summary must expose one BYL promotion host")
for marker in (
    "window.BYLShippingPromotionCheckout",
    "function fetchPromotion()",
    "function mutatePromotion(url, body)",
    "promoConfig.cartUrl",
    "promoConfig.applyUrl",
    "promoConfig.removeUrl",
    "json.extensions['byl-shipping-promotion']",
    "{code: input.value.trim()}",
    "{code: data.applied_code}",
    "$('body').trigger('update_checkout')",
):
    if marker not in js:
        raise SystemExit(f"Missing Store API promotion integration marker: {marker}")
if "Add More" in js or "Add More" in css or "Add More" in form:
    raise SystemExit("Checkout must not render Add More")
if "<form class=\"byl-promo-form\"" in js:
    raise SystemExit("Promo controls must not create a nested form")
if "promoForm.className = 'byl-summary-promo-row byl-promo-form'" not in js:
    raise SystemExit("Promo controls must use a non-form container")

for marker in (
    "woocommerce-form-coupon-toggle",
    "form.checkout_coupon",
    "#coupons_list",
    'smart-coupons/available-coupons',
    ".smart-coupons-available-coupons",
):
    if marker not in css:
        raise SystemExit(f"Missing checkout-only duplicate coupon suppression: {marker}")
if ".woocommerce-form-login-toggle" in css:
    raise SystemExit("Returning customer login must remain available")

if "document.getElementById('payment')" not in js or "paymentHost.appendChild(payment)" not in js:
    raise SystemExit("The complete native #payment container must be moved intact")
for forbidden in ("apple-pay", "google-pay", "payment-request", "express-payment"):
    if forbidden in js.lower() or forbidden in css.lower():
        raise SystemExit(f"Checkout must not hide or hard-code wallet UI: {forbidden}")

for forbidden in (
    "wp_insert_post", "wp_update_post", "wp_delete_post", "wc_create_coupon",
    "update_option(", "add_option(", "wp_create_order", "wc_create_order",
    "INSERT INTO", "DELETE FROM",
):
    if forbidden in php or forbidden in js:
        raise SystemExit(f"Checkout theme layer must not perform business/database writes: {forbidden}")

for marker in (
    "BYLShippingPromotionCheckout",
    "wc/store/v1/cart",
    "wc/store/v1/cart/apply-coupon",
    "wc/store/v1/cart/remove-coupon",
    "wp_create_nonce('wc_store_api')",
):
    if marker not in theme:
        raise SystemExit(f"Missing Checkout Store API localization marker: {marker}")
if theme.count("wp_enqueue_script('byl-v3-checkout'") != 1:
    raise SystemExit("Checkout V2 script must be enqueued exactly once")


# Canonical BYL VI gate: Checkout must consume the global V3 brand tokens.
for forbidden in ("--pc-green", "#00643f", "#f2f8f4", "rgba(0,100,63"):
    if forbidden in css:
        raise SystemExit(f"Checkout VI regression: forbidden private green brand token {forbidden}")
for required in (
    "background:var(--byl-brand)",
    "background:var(--byl-brand-soft)",
    "background:var(--byl-brand-hover)",
    "border-color:var(--byl-focus)",
    "color:var(--byl-brand)!important",
):
    if required not in css:
        raise SystemExit(f"Checkout must consume canonical BYL VI token: {required}")

# Summary is collapsed by CSS at ANY viewport when JS sets aria-expanded=false.
# Mobile starts collapsed while desktop starts expanded, but toggling must be real
# at either width (not just an ARIA update with a still-visible desktop body).
summary_closed_rule = ".byl-progressive-checkout.is-enhanced .byl-summary-card:not(.is-open) .byl-summary-body{display:none}"
if css.count(summary_closed_rule) != 1 or css.index(summary_closed_rule) > css.index("@media(max-width:760px){"):
    raise SystemExit("Order Summary collapsed-body CSS must be a single all-viewport rule")
for marker in (
    "summaryToggle.addEventListener('click'",
    "summaryToggle.setAttribute('aria-expanded',open?'true':'false')",
    "summary.classList.toggle('is-open', !window.matchMedia('(max-width: 760px)').matches)",
):
    if marker not in js:
        raise SystemExit(f"Order Summary toggle/ARIA contract missing: {marker}")

# Payment cannot be marked complete when Woo requires payment but offers no gateway.
if "if ((active === 'payment' || active === 'review') && !paymentConfigured())" not in js:
    raise SystemExit("Payment step CTA must stay disabled without an eligible gateway")
if "if (active === 'payment' && !paymentConfigured())" not in js:
    raise SystemExit("Payment to Review transition must fail closed when no gateway exists")

if "Latest main Checkout V2" not in docs or "one BYL promotion slot" not in docs:
    raise SystemExit("S6 docs must record latest-main ownership and the one-slot promotion integration")


# G3 Review: render Woo's original required checkbox with BOTH configured links in
# one sentence. Move only native terms content below consent controls. Never check it.
for marker in (
    "nativeTerms.querySelector('a.woocommerce-terms-and-conditions-link')",
    "termsLink.closest('.woocommerce-terms-and-conditions-checkbox-text')",
    "privacyBlock.querySelector('a[href]')",
    "document.createTextNode(' and have read the ')",
    "legalText.classList.add('byl-legal-inline')",
    "privacyBlock.classList.add('byl-privacy-merged')",
    "nativeTerms.appendChild(expandedTerms)",
):
    if marker not in js:
        raise SystemExit(f"G3 native Review legal consent contract missing: {marker}")
if ".byl-privacy-merged{display:none!important}" not in css:
    raise SystemExit("G3 duplicate Privacy paragraph must be visually hidden after link transfer")
if "terms.checked = true" in js or "#terms_field input" in js or "privacy-polices/" in js:
    raise SystemExit("G3 must never precheck Terms or hardcode privacy URLs")


# PR40 review: both configured links open the SAME inline legal panel, no new tabs.
for marker in (
    "wireLegalAccordion()",
    "termsLink.removeAttribute('target')",
    "privacyLink.removeAttribute('target')",
    "termsLink.dataset.bylPolicyToggle = 'terms'",
    "privacyLink.dataset.bylPolicyToggle = 'privacy'",
    "event.preventDefault()",
    "event.stopPropagation()",
    "fetchPrivacyPolicy(toggle)",
    "endpoint.searchParams.set('slug', slug)",
    "privacyBody.replaceChildren(safePolicyFragment(markup))",
    "expandedTerms.append(termsBody, privacyBody)",
):
    if marker not in js:
        raise SystemExit(f"PR40 shared inline legal accordion contract missing: {marker}")
if "location.assign(" in js or "window.open(" in js:
    raise SystemExit("PR40 may not navigate Privacy/Terms to new windows")
for marker in (
    "label.woocommerce-form__label-for-checkbox",
    "font:400 14px/1.55 var(--byl-font)!important",
    "accent-color:var(--byl-brand)",
    "[data-byl-policy-privacy][hidden]",
):
    if marker not in css:
        raise SystemExit(f"PR40 unified consent visual contract missing: {marker}")

print("Checkout progressive UX contract check PASS")
