# PR #141 — Checkout Shipping Reference Fidelity V2, Dev visual Owner UAT

Date: 2026-10-17
State: **Dev-only CSS Canary PASS; awaiting Owner visual review; NOT 100% reference-identical**
Parent: PR #140 Save/Billing exact HEAD `a274e9e9f9180313792aa6a448d4ad3972a9b15f`.

## 0. Screenshots of REAL Dev checkout
All images below were obtained by opening the actual `https://dev.beyourlover.com/checkout/` in an isolated headless Chrome browser. **No CSS or form markup injected**, and no real order/payment sent. One temporary guest cart item (synthetic product id `39480`, quantity `5`) and sample Mexico shipping inputs (test-only `synthetic-visual@example.invalid`).

1. [390px — mobile viewport, including the REAL sticky total/CTA](./uat-pr141/dev-shipping-390-top.png)
2. [390px — full shipping form, sticky footer hidden ONLY in capture so fields can be reviewed](./uat-pr141/dev-shipping-390-full.png)
3. [320px — narrow mobile viewport including sticky total/CTA](./uat-pr141/dev-shipping-320-top.png)

Screenshots use a single public synthetic guest cart. Actual Woo rates for this Mexico case were **Flat rate $20.00** and **Free shipping**. Order total for quantity 5 at $79 each was **$395.00**, NOT the reference's $1,099.50 example. No carrier ETA available. The optional RFC Tax ID is country-dependent and left blank in synthetic display. The Newsletter consent remains disabled because real Andy Core API and outbound gates are OFF; guest Save Address remains disabled because there is no authenticated customer.

## 1. Exact physical alignment results (390px)
| Region | Baseline LIVE Dev | PR #141 LIVE Dev | Status |
|---|---:|---:|---|
| Three-step progress top | y=90px, h=83px | y=65px, h=56px | visually compact |
| First Email/Account card top | y=185px | **y=125px** | improved |
| Email field | separate outside label; h=44px | inside floating label; h=51px | improved |
| Shipping Details card top | y=476px | **y=385px** | improved |
| Country field | outside label, native Woo SelectWoo | label inside outlined 53px control, native Woo SelectWoo retained | improved |
| CTA | sticky with extra arrow | single sticky Burgundy `CONTINUE TO PAYMENT`, no arrow | improved |
| Narrow mobile loading-only `— OR —` | intermittently visible | hidden when `#wcpay-express-checkout-element[aria-busy="true"]`; ready wallet remains eligible | conditional |

Changes are **pure mobile CSS** in `theme/beyourlover-v3/assets/css/checkout.css`; no Woo method, rate, country data, order, customer, payment, mail or Checkout Drawer change.

## 2. Release and exact SHA gate
- Draft PR #141 initial CSS commit: `d4b786b715ec3f1dc3a188056c33ee2f06f087cc`. CSS **SHA256 `e79fc6d2fde4530c5176609f329b00ae2800f2ecff44e638c07c97cb45ce1251`**.
- Dev BEFORE CSS SHA256 `87388dd773cace8a1164c1d50976a9bc2764bbc3069bd216f455cf5b2702984b`; mode 644. WordPress home `https://dev.beyourlover.com`, environment `staging`.
- Offwebroot backups/staged source/guarded rollback in `/home/u247343654/.byl-dev-checkout-canary/checkout-owner-visual-v2-20261017/`; `pr141-visual-release.py` `verify` PASS and `deploy` PASS.
- Exact SHA after deployment and both cached & cache-busted public CSS URL SHA PASS. Receipt `pr141-deploy-receipt.json`, **2026-10-17T07:09:50 UTC**. Only ONE CSS file changed; existing `checkout.js` SHA `1a29738b...`, persisted address contract PHP `50ffa7...` unchanged.
- Host backup/release receipts confirmed; no PR merge or WWW deploy; no READY restart.

## 3. Regression tests (REAL Dev, no injected CSS)
- Synthetic guest cart **5 items**; mobile **390px / 320px**: screenshot and geometry PASS, zero horizontal overflow, zero JS pageerrors.
- Original Shipping Country/Province real Woo SelectWoo **9/9** country selections, Germany Bavaria state selection PASS, scroll stability 9/9 across 390/320/1440, zero JS errors. Native Woo rate updates remain HTTP 200. Browser POSTs other than synthetic guest cart and Woo review were blocked.
- Shipping → Payment → Shipping: real US/CA state, phone and ZIP preserved; original Woo two US rates `free_shipping:9` and `flat_rate:10` remain authentic; **Dev payment gateways unavailable** (Payment CTA disabled) as before this CSS.
- Save Address (guest disabled), distinct Billing, Mexico RFC and Newsletter OFF remain as PR #140. No real user/order/customer/mail/payment writes.

## 4. Owner 1:1 UAT remaining mismatches / truth constraints
These are **not passed** as reference-identical:
1. Screenshot reference's **Express $15, 2–3 days / Regular Free, 8–12 days** cannot be shown until those two services and ETAs are verified in the real Woo shipping zone/fulfillment data. Actual Woo currently returned Flat $20 and Free for the synthetic Mexico case. Do NOT invent names, money or arrival estimates.
2. Reference's active offer-checkbox cannot be enabled until Andy Core Newsletter real double-confirmation, API and outbound mail are **separately approved**. OFF status remains disabled and truthfully explained.
3. Reference's **Save this address** active checkbox is available only to logged-in shoppers. Anonymous Checkout keeps it disabled, not a fake local-storage save.
4. Total and quantity are cart dependent; screenshot's $1,099.50 is demonstrative, not a required hardcoded figure.
5. Country-specific RFC / TIN fields appear only for supported destination; maintain accurate Woo validation.
6. A physical iPhone / Owner 1:1 visual sign-off has **NOT** occurred. Open the 390px viewport/full screenshots and the real Dev Checkout; report pixel discrepancies by module for further controlled adjustments.
7. Unrelated intermittent two-second Country/Province performance issue #132 and no available Dev payment gateway are separate open investigations; this CSS-only PR did not claim to solve them.

## 5. Strict closure rules
Dev visual Canary PASSED. **Do not** merge this Draft PR #141 or PR #140 to GitHub main, deploy WWW, activate Newsletter, modify Woo zones/payment or submit actual orders without separate Owner authorization. Next is Owner 1:1 review of supplied screenshots and any specific remaining CSS deltas.
