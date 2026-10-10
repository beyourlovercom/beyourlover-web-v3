# BYL Website V3 · Free Shipping V2 — Destination policy audit

Owner approved V1 Free Shipping page and then requested all four country policies
on the page. Implement the approved V2 guide on Dev first, no WWW.

## Live Dev evidence (read-only Woo shipping API and zone matcher)
- Zone 1: Hong Kong (HK), enabled Free Shipping with no minimum ($0).
- Zone 5: Asia continent plus US mainland / military states. Free Shipping
  threshold $69; simple flat-rate base $10. HK matches Zone 1 first.
  Verified United Arab Emirates (AE) matches **Zone 5 / $69**, despite also
  being configured under the later Zone 4. Do not advertise UAE in $129.
- Zone 4: US-AK, US-HI, Puerto Rico (PR), Mexico (MX). Free Shipping threshold
  $129; base flat $20. The zone location list also contains AE, but live matcher
  resolves AE to Zone 5. Advertised country list must reflect effective mapping.
- Zone 3: RU, VE, MK, MD, CR, AR. Free Shipping threshold $169;
  base flat $30. Do not use the internal label “WAR ZONE” in public UI.
- Zone 0: Rest of World is a **flat-rate-only** fallback and has no standalone
  Free Shipping method. Historical BYL Platform default option $49 differs from
  Andy Commerce persisted default $69. Effective rule source remains
  `byl-platform-legacy`; neither option creates a free method for Zone 0.
  Never advertise $49/$69 as a Rest of World free-shipping guarantee.

Base flat rates are not price quotes: shipping-class extras and selected service
may change the final shipping fee. Checkout remains authoritative.

## Examples verified against real Woo zone matching
HK →1, US-IA/US-CA →5, US-AK/US-HI →4, AE →5,
MX/PR →4, JP/CN/SG/IN →5, RU/VE/MK/MD/CR/AR →3,
DE/FR/AU/CA/BR →0.

## V2 UX
- Keep V1 live-cart progress and Owner-approved wording untouched.
- Add four zone-specific cards with dynamically read policy thresholds and
  disclosed country scope, expandable conditions, and standard rate caveat.
- Country/region selector, with mandatory state field for US, uses a read-only
  public AJAX lookup through the live Woo matching engine. It must not mutate
  the customer's actual shipping address or shipping selection. HTML does not
  list $69 globally as the minimum.
- Query only Woo-supported shipping countries; fail closed when no Free
  Shipping method is available or a location is not served.
- Asian country list includes only countries actually matching Zone 5.
- Avoid any option writes, shipping settings edit, Woo/ERP sync, fake orders,
  payment enablement, user session mutation or migrations.
- Keep Dev checkout G2 / Store API 403 gate closed; preserve WWW.

## Test and UAT
`php scripts/test_free_shipping_page.php` plus JS lint,
PHP lint, Foundation CI, Dev read-only zone checks, AJAX requests for AE,
HK, DE, US without state, US-AK, and desktop/mobile screenshots.
Owner visual UAT pending. No GitHub merge without separately approved exact-head.
