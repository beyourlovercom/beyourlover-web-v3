#!/usr/bin/env python3
"""Canonical image-based Shipping layout contract — source-only and no payments."""
from pathlib import Path
r=Path(__file__).resolve().parents[1]/'theme/beyourlover-v3'
tpl=(r/'woocommerce/checkout/form-checkout.php').read_text('utf-8')
css=(r/'assets/css/checkout.css').read_text('utf-8')
js=(r/'assets/js/checkout.js').read_text('utf-8')
checks=0
def chk(x,why):
 global checks
 if not x:raise SystemExit('FAIL: '+why)
 checks+=1
for x in ['byl-checkout-heading','byl-checkout-return','byl-checkout-mobile-close',
          'byl-checkout-track','byl-contact-card','byl-choice-card','byl-address-card',
          'byl-shipping-rate-section','byl-checkout-billing','byl-duties-note',
          'byl-mobile-order-toggle','byl-mobile-total','byl-checkout-next']:
 chk(x in tpl,'reference module absent: '+x)
chk(tpl.index('byl-contact-card')<tpl.index('byl-choice-card')<tpl.index('byl-address-card'),'module order')
chk(tpl.index('byl-checkout-return')<tpl.index('byl-checkout-track'),'reference header order')
chk(tpl.count('name="checkout"')==1,'exactly 1 native Woo form')
chk('woocommerce_checkout_billing' in tpl and 'woocommerce_checkout_shipping' in tpl,'retain Woo native hooks')
chk('woocommerce_checkout_order_review' in tpl,'retain Woo native order review')
chk('wc_get_cart_url()' in tpl,'cart exit remains a real link')
chk("$cart->get_total()" in tpl and "get_cart_contents_count()" in tpl,'real Woo count and price')
chk('https://dev.' not in tpl,'no hardcoded environment')
chk('priceNode' in js and 'woocommerce-Price-amount' in js,'Woo-native shipping prices')
chk('realPrice' in js and "cost.textContent = visiblePrice" in js,'rate price only from native label')
chk("input.dispatchEvent(new Event('change', {bubbles: true}))" in js,'Woo rate changed via native field')
chk("mobileSummaryToggle.addEventListener" in js,'mobile summary is interactive')
chk('if (nativeTotal && mobileSummaryTotal)' in js,'footer total reflects native Woo recalculation')
chk('byl_progressive_checkout_drawer' not in tpl.lower() and 'byl_checkout_drawer' not in tpl,'no checkout drawer')
for token in ['Checkout Shipping Reference Fidelity V1','body.woocommerce-checkout.byl-secure-checkout-shell .byl-secure-header',
              '.byl-contact-card,.byl-choice-card,.byl-address-card',
              '.byl-progressive-checkout #billing_country_field',
              '.byl-progressive-checkout #billing_state_field',
              '.byl-progressive-checkout #shipping_phone_field',
              '.byl-progressive-checkout #billing_postcode_field',
              '.byl-checkout-action-bar',
              '.byl-mobile-order-toggle','.byl-checkout-summary:has(.byl-summary-card.is-open)']:
 chk(token in css,'missing reference CSS token: '+token)
chk('Reference Fidelity V1 payment return gate' in css,'Payment back-to-Shipping rule')
chk('.byl-checkout-step[data-byl-step="shipping"]:not(.is-active)>.byl-step-body' in css,'Shipping body collapses when Payment active')
chk('.byl-checkout-step[data-byl-step="shipping"]:not(.is-active)>.byl-step-toggle' in css,'Shipping edit toggle returns on Payment')
chk('woocommerce_checkout' not in js.lower() or 'place_order' in js,'JS Woo native submit retained')
print('PASS Checkout Shipping Reference Fidelity V1: %d source-only checks, zero orders/payments' % checks)
