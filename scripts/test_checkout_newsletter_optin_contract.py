#!/usr/bin/env python3
"""Checkout marketing opt-in is independent of Woo and consent-required."""
from pathlib import Path
r=Path(__file__).resolve().parents[1]/"theme/beyourlover-v3"
template=(r/"woocommerce/checkout/form-checkout.php").read_text(encoding="utf-8")
js=(r/"assets/js/checkout-newsletter.js").read_text(encoding="utf-8")
assets=(r/"inc/theme-os-assets.php").read_text(encoding="utf-8")
css=(r/"assets/css/checkout.css").read_text(encoding="utf-8")
assertions=0
def check(ok,reason):
 global assertions
 if not ok:raise AssertionError(reason)
 assertions+=1

for token in (
 "YBY_Newsletter_REST_Controller",
 "YBY_Newsletter_REST_Controller::subscribe_ready()",
 "YBY_Newsletter_REST_Controller::policy_version()",
 "data-byl-newsletter-ready",
 "data-byl-newsletter-policy",
 "data-byl-newsletter-endpoint",
 "andy-core/v1/newsletter/subscribe",
 "data-byl-newsletter-consent",
 "Notify me of exclusive offers",
 "Email offers are not available yet.",
 "disabled(!$byl_newsletter_ready)",
):
 check(token in template,"Missing real Andy readiness gate "+token)
check('data-byl-marketing-optin-slot hidden' not in template,"Remove fake empty opt-in slot")
check('<input type="checkbox" data-byl-newsletter-consent value="1"' in template,"Keep consent optional")
check('type="checkbox" data-byl-newsletter-consent value="1" name=' not in template,"No native Woo order opt-in name")

for token in (
 "if (!ready || !permitted)",
 "consent.checked = false;",
 "consent.disabled = true;",
 "if (!consent.checked || inFlight) return",
 "email.validity.valid",
 "marketing_consent: true",
 "consent_policy: policy",
 "source: 'byl_checkout_optional'",
 "source_page: window.location.pathname.slice(0, 255)",
 "credentials: 'omit'",
 "referrerPolicy: 'no-referrer'",
 "method: 'POST'",
 "fetch(url.toString(), {",
 "response.ok",
 "result.success !== true",
 "You are not subscribed yet",
 "Email offers are unavailable. You can still complete checkout",
):
 check(token in js,"Missing consent/privacy guard "+token)
check("WooCommerce" not in js or "NOT part of Woo order submission" in js,"No Woo dependency")
check("localStorage" not in js and "sessionStorage" not in js and "navigator.sendBeacon" not in js,
      "Never store or silently transmit emails")
check("checkout.js" in assets and "checkout-newsletter.js" in assets,"Enqueue only on checkout")
check("['byl-v3-checkout']" in assets,"Existing native checkout initializes first")
check(".byl-checkout-marketing-label" in css and "#93002c" in css,"Canonical burgundy appearance")
check("byl_checkout_drawer" not in js and "checkout-drawer.js" not in assets,"No retired checkout drawer")
print("PASS Checkout Andy Newsletter consent gate: %s assertions (no email sent)" % assertions)
