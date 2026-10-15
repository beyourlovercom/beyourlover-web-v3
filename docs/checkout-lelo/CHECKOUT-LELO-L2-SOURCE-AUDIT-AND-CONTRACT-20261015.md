# Checkout LELO Three-Step — L2 Source Audit & Integration Contract
Date: 2026-10-15 | Repo: `beyourlovercom/beyourlover-web-v3` | base `main@1438768d919e6a848ae71c9a7ebfa3ceeafc80ea`
Status: **L2 audit partially verified, design locked, live Woo/Dev configuration unverified**. Related: Issue #89, Cart #94, E2E #87, FX #113 / PR #116, G5.

## 0. Authority, permissions, and scope
- Owner Design PASS for **Cart → Shipping → Payment** (Option B Drawer); the latest Shipping phone/ZIP/Tax ID ordering, guest-email exact UX, and Payment mobile+desktop visuals are fixed. Canonical companion: `BYL-CHECKOUT-LELO-3STEP-CANONICAL-V1-HANDOFF-20261015.md`.
- This PR is **documents only**. No runtime code, payments, checkout forms, orders, customers, email, Woo settings, active gates, Dev/WWW deployment, or `main` merge. Do not claim live UAT or passing CI without run evidence.
- The seven approved/source images are archived in the Owner's ChatGPT Library zip `BYL-Checkout-Canonical-V1-Design-and-Handoff.zip`; `references/` paths in the Handoff describe that bundle, **not paths present in this GitHub PR**. Before L3 production UAT, import approved visual assets with explicit permission or use Owner's bundle as the design comparator.

## 1. Actual main code (read-only verified 2026-10-15)
| Evidence | Confirmed behavior | L2 impact |
|---|---|---|
| `theme/beyourlover-v3/page-checkout.php` | `[woocommerce_checkout]` classic shortcode; `get_header('checkout')`; existing `/checkout/` fallback | Avoid forced Woo Blocks takeover. Drawered runtime must not suppress native checkout host |
| `theme/beyourlover-v3/woocommerce/checkout/form-checkout.php` | Four UI sections: `address`, `delivery`, `payment`, `review`; final `#order_review` and Woo native payment rendering | Merge address + delivery into **Shipping**, move review/invoice into **Payment**; no fourth customer-visible stage |
| `theme/beyourlover-v3/assets/js/checkout.js` | `steps=['address','delivery','payment','review']`, `setStep`, `updateAction`, `deliveryOptions`; click of native `#place_order` only on Review; listens to Woo `updated_checkout`, `checkout_error` | Refactor navigation/CTA only after native payment host/recalc events are tested, preserve busy/3DS lock |
| Same JS, lines 16–29 | **Dev-only automatic checkbox precheck** via `devPrecheck`, `consentChoice.set(input.name,true)` for `terms` and `cr_customer_consent` | Security/consent regression gate. Remove auto-selection; keep optional marketing consent opt-in only. Do not copy into Production |
| `theme/beyourlover-v3/inc/checkout.php` | Unsets company fields/legacy phone, registers required `shipping_phone` under billing fields; Dev only disables Woo terms checkbox via filters | Align phone ordering (before postcode) and disclosure. Legal/consent behavior requires separate contract review, don't silently delete server checks |
| `theme/beyourlover-v3/inc/cart-drawer.php` and `assets/js/cart-drawer.js` | Existing Dev-only Cart Drawer uses Woo Store API; checkout URL resolves `wc_get_checkout_url()`; Cart fallback preserved | Reuse existing Cart session/CTA; Checkout Drawer takeover needs explicit runtime/interop work and fallback |
| `theme/beyourlover-v3/inc/cart-experience.php` | BYL49 and shipping promotion projection use authoritative Andy Commerce + WC shipping zones/packages and selected rate | Do not recalculate discounts or shipping total in new Theme UI |
| `theme/beyourlover-v3/theme-os.json` | Checkout is `protected-commerce-ui` module, depends on cart+shipping-ui; explicit file registry | Register future Checkout Drawer assets, tests and Bootstrap safely |
| `theme/beyourlover-v3/header-checkout.php` | Existing full-page focused Checkout Header available | Keep `/checkout/` fallback even if Drawer becomes primary |
| PR #90 | Earlier rejected planning draft still **open/draft** and predates the new Option B Owner Design PASS | Supersede earlier four-stage visual contract; do not merge obsolete PR without reconciliation |
| PR #116 | FX parity contract open/draft, not merged; existing WWW YayCurrency production behavior separate | Do not assume currencies/FX/gateway mappings from placeholders |

**Not established by code review:** actual Woo site gateway IDs and enablement; shipping zones/rates/ETAs by nation; Dev Woo account creation setting, Mail OS delivery configuration, exact consent hooks; Andy Core Country Notice API; tax and tariff jurisdiction flags; FX runtime results; actual Drawer collision test. All remain **PENDING**; do not make unverified claims.

## 2. L2 frozen UI and server contracts

### L2-A Navigation / hosting
1. Cart (existing Drawer) → Shipping (single panel: login/email, rates, address/tax ID/notices) → Payment (methods, Woo final review + order total); native `#place_order` remains only submit authority. All previous `delivery` and `review` steps disappear from **customer-visible navigation**.
2. Checkout Drawer is a user-approved visual architecture **not yet a technically approved deployment**. Use a single Woo Classic checkout form mounted once (no duplicate form/iframe). On block, JS failure, disabled feature flag, invalid session, order-pay or required 3DS redirect, send the user to secure `/checkout/` fallback preserving supported state.
3. On mobile, one sticky CTA and collapsible order summary. Desktop cart/contact/total left, payment methods right. Focus returns to opener when Drawer closes; Escape/back/focus trap, scroll locking and small-screen keyboard must pass UAT.

### L2-B Shipping + Woo state authority
1. Guest email exact LELO reference: `Log in or create an account`, `Email Address*`, **no email helper paragraph**, optional unchecked `Notify me of exclusive offers`. Logged-in: securely identify self and `Edit your profile`; no email-based unauthenticated profile exposure.
2. Woo shipping address: country*, first+last*, address1*, optional apt collapsed, city*, state by Woo country contract, phone* **before** postal code with one full-width privacy line, postal code with country-dependent required state, separate bottom full-width customs ID *when applicable*, optional organization collapsed. Billing same as shipping by default; gateway-required distinct billing edit supported.
3. Shipping selection **inside Shipping**, from active Woo package rate/method ID and real shipping recalculation; never hardcode Express 2–3 days, Standard free, shipping cost or currency. On address/rate change trigger/reconcile `update_checkout`, keep selected `shipping_method` checked and Woo totals authoritative.
4. No usable rate must block progressing with a recoverable accessible message; country toggles must clear stale tax ID + notices without discarding legitimate address data unexpectedly.
5. Woo discounts, BYL49 post-discount pre-tax subtotal and FX are external authorities. Payment displays the chosen shipping method but does not solicit it again.

### L2-C Payment + G5
1. Enumerate only eligible current Woo payment gateways by session/cart/country/currency/device; do not fake Stripe/PayPal/Alipay/Google Pay. Render provider-hosted sensitive fields; never log PAN/cardholder data.
2. Payment `PAY NOW` delegates to native Woo button once after field checks. Preserve Woo nonces, order review, consent/legal server requirement if applicable, 3DS/SCA/redirect, G5 anti-duplicate and recovery. Do not treat JS `submitting` as a provider idempotency guarantee.
3. At zero total, pending/manual, redirected or provider-auth flows adapt action text truthfully. Error/failure must not silently retry or create duplicate orders. No extra Review page or mandatory distractions.
4. Existing Dev experiment selecting Terms/review-invite automatically is **prohibited in L3**; no prechecked marketing, legitimate legal consent only.

### L2-D Identity / Mail OS
- User asks to create a WordPress customer account from email. Do not create customer on **input/blur/keystroke**: enables mass registrations, mailbox spam and account enumeration.
- Preferred secure Woo contract: collect email at Shipping; at validated checkout/order-commit checkpoint (and only where store Woo policies allow) create or associate account *after ownership/conflict checks*, deduplicate by email/order; issue **account setup link** and send exactly one notification through approved Mail OS. Never reveal an existing profile from typed email or email a plaintext password.
- Need Owner + Mail OS separate write authorization before any actual account generation/emails. Pending Woo account setting read-only snapshot and existing-email checkout rules.

### L2-E Country Notices and tax IDs
- Country Notices admin proposed `Andy Core → Checkout OS → Country Notices`: WP admin-manageable **versioned** copy data; Global, Region, Country, conditional overlays; `Country > Region > Global` for overrides, composable independent conditions, locale/version/audit/preview/rollback. Sanitize content, capability gates, no raw HTML injection, cache invalidation when destination changes.
- Notice words must match financial facts: US `This order includes all local taxes` **only if checkout tax state really supports it**; otherwise no unconditional claim. EU VAT/clearance/IOSS/DDP/DAP depends on actual tax/logistics contract.
- Tax ID rules are separate validation/PII storage: Mexico RFC, Brazil CPF/CNPJ conditional, Chile, Argentina, South Korea PCCC/ID, Peru; country-specific label/length/format/reason should be confirmed by logistics. Field after ZIP, own row; server-side required enforcement only where legally/logistically validated.
- Norway VOEC is seller's tax registration scheme, **not mandatory seven-digit personal buyer VOEC**. If appropriate present seller/threshold notice and seller declaration, not buyer identity capture. Never put tax IDs into analytics or front-end logs.

## 3. Proposed L3 change sets — independent Draft PRs only

| Slice | Area/files likely involved | Contract tests |
|---|---|---|
| L3-01 three-step shell and Checkout Drawer safe entry | `form-checkout.php`, `checkout.js`, `checkout.css`, `checkout-shell.css`, new drawer assets in `theme-os.json` | Three visible stages, fallback no-duplicate-form, M320/M390/M14/PC1440, focus/ARIA, native Woo form re-mount |
| L3-02 Shipping UX | `inc/checkout.php`, `form-checkout.php`, `checkout.js` | Guest/login email, country-sensitive state/ZIP, phone-before-ZIP, optional collapses, Woo rate recalculation |
| L3-03 Country Notices + Tax ID Contract | New Andy Core admin/config/API in its **own source repo** and Checkout thin Theme renderer | Revisions, role/nonce, country switching, tax statements, conditional rule server validation, Norway negative case |
| L3-04 Payment and review | `form-checkout.php`, `checkout.js`, Woo checkout native host | Eligible gateways only; single CTA; 3DS, errors, subscription consent, no duplicate submission; no test charge absent authorization |
| L3-05 Account/Mail lifecycle | Woo/Andy Core/Mail OS integration; **separate approval** | existing vs new email, concurrent double-submit, rate limiting, no email during preview, no plaintext credentials |
| L3-06 Theme/Cart/G5/FX integration contract | affected Theme OS module, isolated fixtures | Cart→Shipping→Payment; terms permissions, BYL49 & FX totals parity, no gateway regression |

## 4. Read-only runtime evidence needed to close L2
- [ ] **Dev** screenshot/read-only config: Woo Classic checkout mode, merchant Woo version, account creation options, guest checkout, saved-address handling and whether `/checkout/` is classic.
- [ ] List of Woo enabled gateway IDs and applicability by country/currency, no credentials disclosed.
- [ ] Woo Shipping Zones/Methods actual `rate_id`, tax settings, destination order totals and ETAs or shipping-provider SLA evidence. In particular Express availability **not assumed**.
- [ ] Current checkout consent plugins and legal Terms behavior: confirm no automatic `terms` or `cr_customer_consent` pre-check on Dev after L3.
- [ ] Andy Core Checkout OS / Country Notices implementation status; if absent, new plugin-side Draft contract is required, no Theme-side fake settings.
- [ ] Existing Mail OS customer-created message hooks and opt-in; separate SMTP approval later.
- [ ] Woo/YayCurrency/FX switching `USD/EUR/GBP/AUD/CAD` and real gateway/checkout totals at read-only or synthetic level; PR #116 not an implicit pass.
- [ ] Checkout Drawer compatible with G5 pay-for-order/order-pay and thank-you route fallback.
- [ ] Owner confirm disputed country ID validation and US/EU tax disclosure truth conditions after logistics and tax review.

## 4A. Public Dev read-only observations (2026-10-15)
- From authorized **us-macmini** remote read-only HTTPS GET, `https://dev.beyourlover.com/checkout/` returned HTTP 200 **after redirect to** `https://dev.beyourlover.com/cart/` under a clean/no-item session. The returned page contains `data-byl-cart-drawer`; this **does not prove** Checkout Drawer behavior with items. No items were added and no request was submitted.
- The public Dev theme asset `/wp-content/themes/beyourlover-v3/assets/js/checkout.js` returned HTTP 200, 28,733 decoded characters, containing the **four-stage array** `['address', 'delivery', 'payment', 'review']`, `const devPrecheck`, and `nativeButton.click()`. This confirms the legacy Checkout client bundle remains publicly served on Dev; **not a claim that the actual Checkout view executed the script**, since the empty-cart visit redirected.
- **Necessary next observational gate:** a legitimately existing non-transactional Dev test Cart session or authorized isolated synthetic Woo fixture to view the actual loaded Checkout and gateway list. Never create a real order or payment just to obtain an image.

## 5. Gates / results
- **Source audit**: main checked, concrete blockers identified: **PASS (read-only)**.
- **L1 visual design**: Owner PASS recorded 2026-10-15.
- **L2 final functional freeze**: **PARTIAL / HOLD** until above runtime/config read-only evidence and Country Notices/identity contract decisions.
- **L3 code, CI, Dev runtime UAT, Cart Owner Final, G5 #87 E2E, WWW T6**: **NOT STARTED / independent HOLD** for this PR. No claim of transaction or mail verification.
- No merge into main, no Dev/WWW deployment, no order/payment/account/email/DB writes, no READY reboot. Only specific exact-head CI and Owner approval can advance.
