#!/usr/bin/env python3
"""Canonical Checkout Standard Page contract (source-only; never calls Woo, payment, DB or mail)."""
from pathlib import Path
import re, json

ROOT=Path(__file__).resolve().parents[1]
THEME=ROOT/"theme/beyourlover-v3"
def content(name): return (THEME/name).read_text(encoding="utf-8")
form=content("woocommerce/checkout/form-checkout.php")
php=content("inc/checkout.php")
assets=content("inc/theme-os-assets.php")
js=content("assets/js/checkout.js")
css=content("assets/css/checkout.css")
cart=content("inc/cart-drawer.php")
cartjs=content("assets/js/cart-drawer.js")
page=content("page-checkout.php")
manifest=json.loads(content("theme-os.json"))
doc=(ROOT/"docs/checkout/CHECKOUT-CANONICAL-STANDARD-PAGE-20261016.md").read_text(encoding="utf-8")
checks=0
def ok(cond,message):
    global checks
    if not cond: raise SystemExit("FAIL Standard Checkout: "+message)
    checks+=1

ok("Checkout Drawer: RETIRED PERMANENTLY" in doc and "Cart Drawer: RETAINED" in doc, "canonical owner source")
for old in ("assets/css/checkout-drawer.css","assets/js/checkout-drawer.js","assets/css/checkout-visual-v2.css","assets/js/checkout-visual-v2.js"):
    ok(not (THEME/old).exists(),"retired asset must not exist: "+old)
    ok(old not in str(manifest),"retired asset cannot be in theme manifest")
for source in (php,assets,js,css,form,page,cart,cartjs):
    ok(not re.search(r"byl_checkout_drawer|byl-checkout-drawer|BYLCheckoutDrawer|checkout-visual-v2|checkout\.drawer",source,re.I),"no active Checkout Drawer references")

ok(form.count('name="checkout"')==1, "exactly one native Woo checkout form")
ok(form.count('do_action(\'woocommerce_checkout_before_customer_details\')')==1, "native Woo express hook exactly once")
ok("wc_get_checkout_url()" in form and "woocommerce_checkout_order_review" in form, "real Woo action and native order-review")
ok(form.count('data-byl-step="shipping"')==1 and form.count('data-byl-step="payment"')==1, "two native checkout panels")
ok("data-byl-track=\"cart\"" in form and "data-byl-track=\"shipping\"" in form and "data-byl-track=\"payment\"" in form, "3-stage cart / shipping / payment progress")
for extra in ('data-byl-step="address"','data-byl-step="delivery"','data-byl-step="review"'):
    ok(extra not in form, "no extra stage "+extra)
ok("const steps = ['shipping', 'payment']" in js and "setStep('shipping', {scroll:false})" in js, "2-step JS matches Woo template without initial scroll jump")
ok("function setStep(step, {scroll = true} = {})" in js and "if (heading && scroll) heading.scrollIntoView(" in js, "only user-driven step changes may scroll")
ok("nativeButton.click()" in js and "function paymentConfigured()" in js, "Woo native payment submission retained")
ok("if (active === 'payment' && !paymentConfigured())" in js, "no payment gateway fail-closed")
ok("$(document.body).on('checkout_error'" in js or "$(document.body).on('checkout_error'," in js, "native Woo payment error recovery")
ok("review.querySelector('#payment')" in js and "paymentHost.appendChild(payment)" in js, "latest native Woo provider #payment DOM moves intact")
ok("function validateAddress()" in js and "function deliveryOptions()" in js, "real shipping validation and delivery options")
ok("'billing_country' => 5" in php and "'billing_state' => 60" in php and "'billing_postcode' => 85" in php, "native country/state/postcode sequencing")
ok("unset($fields['billing']['billing_phone'])" in php and "unset($fields['shipping']['shipping_phone'])" in php, "no duplicate phone fields")
ok("'shipping_phone'" in php and "'required' => true" in php and "'autocomplete' => 'tel'" in php, "one required Woo shipping phone")
ok("unset($fields['shipping']['shipping_company'])" in php, "hide shipping company")
ok("['billing_company']['required'] = false" in php, "billing company optional instead of fake field")
ok("update_checkout" in js and "updated_checkout" in js and "country_to_state_changed" not in js, "Woo recalculation events preserved without overriding native country data")
ok("'cartUrl' =>" in assets and "'applyUrl' =>" in assets and "'removeUrl' =>" in assets, "Woo Store API promotion authority preserved")
ok("data-byl-promotion" in form and "function fetchPromotion()" in js and "function mutatePromotion(url, body)" in js, "one real promo host / Store API logic")
ok("woocommerce-form-coupon-toggle" in css and "form.checkout_coupon" in css, "duplicate Woo coupon suppressions")
ok("byl_v3_cart_drawer_runtime_enabled" in cart and "data-drawer-checkout" in cart and "wc_get_checkout_url()" in cart, "Cart Drawer retained with canonical Woo checkout CTA")
ok("byl_checkout_drawer" not in cart and "byl_checkout_drawer" not in cartjs, "old drawer route absent from Cart")
ok("do_shortcode('[woocommerce_checkout]')" in page and "[woocommerce_checkout]" in page, "standard page serves Woo checkout shortcode")
ok("wp_enqueue_script('byl-v3-checkout'" in assets and assets.count("wp_enqueue_script('byl-v3-checkout'")==1, "single canonical script registration")
for forbidden in ("--pc-green","#00643f","#f2f8f4"):
    ok(forbidden not in css, "keep BeYourLover burgundy VI")
for forbidden in ("wp_create_order","wc_create_order","process_payment","wp_insert_post","update_option("):
    ok(forbidden not in js and forbidden not in php, "no business writes in theme Checkout code")
print(f"PASS Standard Checkout Cart → Shipping → Payment: {checks} source-only assertions, 0 transactions")
