# Checkout LELO Visual Upgrade — L0 Findings / L1 Candidate / L2 Interaction Contract

## OWNER CHANGE REQUEST — 2026-10-13 — supersedes all proposed four-step Checkout design requirements below

> **FIRST L1 MOCK = OWNER VISUAL FAIL. NO OWNER DESIGN APPROVAL. DO NOT IMPLEMENT OR DEPLOY.**
> **NEW REQUIRED CUSTOMER JOURNEY = Cart → Shipping → Payments (THREE visible steps total).**
> Step 1 `Cart` is the existing **Cart Drawer**, not an added Checkout pane. Keep `/cart/` fallback.
> Step 2 `Shipping` is the first Checkout page stage and **combines address with delivery/shipping selection**.
> Step 3 `Payments` **combines native Woo payment with final order review/confirmation**; there is NO separate visible Delivery or Review step.
> Owner must first review a **single continuous Cart Drawer → Checkout** design preview at PC1440 / M390 / M320, with consistent BYL tokens, CTA, copy and summary. Obtain Owner Visual PASS **before final L2 contract, L3 implementation, Dev deploy or merging docs as design signoff**.
> The legacy four-stage Apple Checkout is **existing runtime evidence only**, not an approved design target. Woo shipping calculations, legal consent, order review and G5 3DS/payment security remain authoritative behind the simplified UI. A Checkout Drawer is not approved; `/checkout/` remains primary Checkout surface unless Owner separately changes the architecture.
> See [Issue #89](https://github.com/beyourlovercom/beyourlover-web-v3/issues/89) for current acceptance gates; Issue #87 remains HOLD.

> 2026-10-13 · Issue [#89](https://github.com/beyourlovercom/beyourlover-web-v3/issues/89) · **L1 ORIGINAL DESIGN REJECTED — Owner Visual FAIL**
> This is a design and source audit, not a checkout/payment patch and not a Dev/WWW deployment.

## 0. L0 read-only source inventory

Canonical GitHub `main` inspected on 2026-10-13:

| Module | Git Blob SHA-1 | Ownership/notes |
| --- | --- | --- |
| `theme/beyourlover-v3/woocommerce/checkout/form-checkout.php` | query current `main` again before implementation | Native Woo Classic checkout form; all four BYL steps, Woo hooks, review and order summary. Do not replace transaction controls. |
| `theme/beyourlover-v3/inc/checkout.php` | `cb51ad07b2c0bd0287b4c65d7da107876e101c06` | Required `shipping_phone`, omit billing phone/company, native Woo checkout fields. |
| `theme/beyourlover-v3/assets/js/checkout.js` | `0144790568ca6305eea0096e75f186564de237a8` | Four-step UX, Woo shipping/payment bridge and G5 processing lock. **High-conflict with G5**; avoid broad rewrites. |
| `theme/beyourlover-v3/assets/css/checkout.css` | `39b7a1622ca4db62d9f65a202f6779e79b5526aa` | Grid, step cards, input/form visual, summary and mobile CTA; primary candidate for scoped style refinements. |
| `theme/beyourlover-v3/inc/theme-os-bootstrap.php` | `7db78ba1e3dff11bf5036b4009850f388234b327` | Module order; don't add a new plugin or reorder Woo bootstrap. |

Potential parallel owners: Cart V2/Theme OS, G5 #71, Checkout Compact Notice draft PR #88, Thank You and Order Pay, Andy Commerce discounts; obtain fresh HEAD/CI before any L3 work.

### Actual Dev browser baseline — isolated guest read-only evidence

- Authorized first-party OAuth MCP connected to `007windows` READY, used independently launched headless Microsoft Edge. No Chrome/READY restart, no authenticated session or server file modifications.
- Isolated anonymous cart fixtures: Dev `?add-to-cart=39480` then `GET /checkout/`, one temporary test cart per viewport; zero Checkout POST, Place Order/payment, provider notification or ERP activity intentionally performed.
- PC1440 and M390: Checkout HTTP 200, four step nodes and Address first, 0 available guest gateway radios while G2 closed, no JS errors, no horizontal overflow.
- M390 summary initially closed / `aria-expanded=false`; PC1440 initially open / `aria-expanded=true`. Current typography Poppins and brand `#9b3749`; card border radius `13px`; site content max width ~1200px.
- Screenshots/local JSON on 007windows: `C:\Users\Administrator\Downloads\BYL-LELO-L0-BASELINE-20261013\checkout-{390,1440}.png` and `baseline.json`.
- Independently read served Dev static assets: Checkout JS HTTP 200 SHA256 `e6ee86d09a4e06f54e7cd2a65254f56c7994b58caab353fb2b0f3377c052b13d`; CSS HTTP 200 SHA256 `082389cae0fcea0d6e7c8684b946ab0bd0106766190cc8feff6eabe270445826`. These are **runtime evidence hashes**, not yet separately established as exact GitHub-`main` byte parity.
- The served Dev JS includes current G5 `submitting`/`aria-busy` behavior and the Dev checkbox precheck experiment below.

### L0 consent precheck discrepancy — carry as a separate, tracked risk

Both `main` source and Dev-served checkout JS contain a Dev-specific code path:

```js
const devPrecheck = window.location.hostname === 'dev.beyourlover.com';
const consentSelector = 'input[type="checkbox"][name="cr_customer_consent"], input[type="checkbox"][name="terms"]';
// syncConsentCheckboxes initializes consentChoice.set(input.name, true);
// and assigns input.checked from the map.
```

**Verdict:** code-level evidence of a Dev-only automatic preselection experiment, **not** permission to treat consent as valid. At L2/L3 exclude auto-opt-in from the LELO design and coordinate a separately reviewed native Woo legal-consent correction. Do not silently turn this into a Production change. Verify actual Dev Review behavior in a permitted no-submit UAT later; anonymous first-step screenshot alone does not prove visible Review checkbox behavior.

## 1. LELO source/reference scope

- Public official `https://www.lelo.com/` and `https://www.lelo.com/ru/cart` reviewed as *broad premium retail visual inspiration*. They do **not** constitute a verified LELO Checkout step-by-step specification; the exact original LELO checkout form was **not inspected**.
- Owner's exact Checkout reference/screenshots are still needed to freeze L1 design; do not claim a pixel-perfect LELO clone.
- Reinterpret premium sparse typography, thin dividers, generous rhythm and compact order details in **BYL branded** black/white + existing burgundy `#9b3749`; no LELO graphics/branding/promotion code or copied creative assets.

## 2. L1 candidate produced (offline preview, not a website deploy)

- Standalone, inert local responsive HTML concept and static PNG previews rendered at PC1440, M390 and M320. Sample product/$79 marked `SAMPLE CART · VISUAL PREVIEW`; shipping/tax are explicitly uncalculated, and preview buttons do not POST or create orders.
- Desktop: simplified BYL header + progress tabs, elegant field card and sticky order summary; mobile: compact 4-step row, summary collapsed, one fixed safe-area-aware CTA.
- Prototype explicitly uses BYL brand/burgundy, PingFang/-apple-system font stack, thin lines and square-ish modest radii. No third-party product images; thumbnail is abstract placeholder.
- Offline Chromium layout checks: PC1440, M390, M320 all `scrollWidth == clientWidth` (zero horizontal overflow); mobile summary collapsed and desktop summary expanded.
- **Not yet accepted**: L1 Owner Visual PASS. Preview artifacts are attached to the ChatGPT project handoff for review; upload/host the approved version separately before final Dev code PR if archival screenshots are required.

## 3. L2 preliminary behavior matrix — draft pending L1 design acceptance

| Step/state | Visual requirement | Native owner / fail-closed requirement |
| --- | --- | --- |
| Address empty/incomplete | Visible inline required errors and focus to first invalid | Woo checkout field validation is authority; no fake acceptance of blank address/phone |
| Address valid/auto-complete | Readable two-column desktop, single-column safe M320 | Native Woo input/update hooks and supported address-suggestion provider; do not inject address data |
| Delivery pending/rates | Skeleton or pending label, then selectable real rates | Woo packages/rates/totals only; no hardcoded free shipping or fake thresholds |
| Promo missing/invalid/applied | Single expandable/compact promo area and message | Andy Commerce/Woo server verification; no second discount engine |
| Payment unavailable | Neutral no-payment UI, no active payment submission | G2 guest gate remains CLOSED; no fictional gateway icons / 'successful payment' UI |
| Payment 3DS processing | Keep one processing state and prevent double-taps | Existing G5 #71 UI lock/Woo native submit authority; don't edit payment JS blindly |
| Payment rejected / retry | Focus native error; retry only after Woo settles and resets | G5 cancellation/error/retry authority; no autonomous payment retry |
| Review/Terms/Privacy | Native required checkbox and optional marketing/review separate | No opt-in precheck; Woo consent and native docs, privacy policy links authoritative |
| Order summary open/closed | Desktop open / mobile closed by default; toggle keyboard/ARIA | Preserve PR #37 DOM visibility and Woo real prices/tax/shipping |
| Mobile fixed action | Exactly one sticky CTA, safe-area inset, no obstructed legal or input | M320, M390 and iPhone 14 manual UAT; keyboard overlays must not hide CTA |
| Woo notices | Clean, persistent native severity and meaningful labels | Follow Notification C2 owner, no hidden native Woo error messages |

## 4. Exit gates / forbidden operations

- L0 source and live anonymous layout capture: **PASS**. LELO exact reference/visual boundary: **pending Owner confirmation**.
- L1 preview candidate: **prepared**; **Owner Visual PASS PENDING**, do not mark complete.
- L2 matrix: **Draft prepared**, not approved; refine after L1 Owner feedback.
- L3 Theme OS Checkout implementation is **NOT STARTED**; do not stage or deploy unapproved design changes.
- L4 real Dev actual implementation/UAT and L5 Owner Final PASS: **NOT STARTED**; cannot be inferred from nontransaction baseline screenshots.
- Issue #87 Commerce Integration continues **HOLD** until independent Cart Final PASS + Checkout Final PASS + current G5 evidence, with separate integration authorization.
- No WWW/ERP/AfterShip, Woo payment/order creation, DB changes, credentials, new plugins, machine restart or GitHub main merge under this document.
