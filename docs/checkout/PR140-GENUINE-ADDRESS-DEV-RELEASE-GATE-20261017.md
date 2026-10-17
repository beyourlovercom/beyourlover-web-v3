# Checkout Genuine Address V1 — DEV release and rollback gate

Date: 2026-10-17
Status: **Draft PR #140, source + isolated browser PASS, NOT deployed.**
Depends on Newsletter V1 OFF-state deployed to `dev.beyourlover.com` (branch HEAD `6231a2acd5c6913b6c0b6b27928e00339c08e077`).
Source of truth: exact PR #140 HEAD, re-check before any release.

## 0. Product & Woo facts (read-only Dev)
- Shipping form currently uses native Woo **billing_*** fields as delivery inputs.
- `woocommerce_ship_to_destination=billing`, `woocommerce_tax_based_on=shipping`, guest checkout YES, Checkout registration NO, My Account registration NO. Do not change Woo options.
- Woo's `WC_Checkout::create_order` copies posted billing/shipping data to `WC_Order` before `woocommerce_checkout_create_order` (pre-save). `process_customer` normally updates logged-in `WC_Customer` regardless of an Owner UI "Save Address" checkbox; the native opt-in must guard this.
- Guest address is never persistent. Show a disabled Save checkbox and login instruction; do not silently create a user.
- Alternative invoice address must never change native Checkout delivery/shipping zone/tax data before Woo calculates methods.

## 1. Only five theme runtime files

| Path relative to theme | VERIFIED LIVE Dev BEFORE SHA256 | Exact PR #140 source file SHA256 |
|---|---|---|
| `inc/checkout.php` | `104ad3d47a2a2c79dac24e59256cac5154967724f8697220a45d6b8b4fb5d300` | `6ccf04c7bb3946b2c32d14ee150e67e4ff940080a612082f60031ba762033812` |
| **NEW** `inc/checkout-address-contract.php` | **ABSENT** | `b19e531ac4f4138bace967e35f01ee704cb3c8da6e8eb49576cfa7a940c8e73a` |
| `woocommerce/checkout/form-checkout.php` | `1f4544ce5baedb2189e08e4a652ad3cdfc33d175a4672950b9ddc1a22792d392` | `ebcc375b2b9484adef46e6c7d1b7e3eac46e3077fe3643d4740456cd17a1fb48` |
| `assets/js/checkout.js` | `007c2bb0e34dc75c8bedd987d43d4f4f1ccac6e528919557145ea69e643d89cf` | `e7a71a1c52046e402b27d87ea4fa0704443f86b7e1104ef4e003cfc3f6b7df27` |
| `assets/css/checkout.css` | `88e1ef5322db11bb67958f74e97c9e01b3f5623932a6b6eae257ca83cc77f362` | `87388dd773cace8a1164c1d50976a9bc2764bbc3069bd216f455cf5b2702984b` |

Site root (ONLY Dev): `/home/u247343654/domains/dev.beyourlover.com/public_html/wp-content/themes/beyourlover-v3/`.
All four currently installed files are regular mode 644 owned by `u247343654`. Site `home=https://dev.beyourlover.com`, `WP_ENVIRONMENT_TYPE=staging`. Refresh all before-SHA and HEAD at release time; STOP on drift.

## 2. Architecture contract
1. Address visible in Shipping is Woo's current native `billing_*` field set. It continues to determine live shipping method availability and shipping-based tax calculation.
2. Real same-address checkbox checked initially. If unchecked, one **separate native-input** `byl_invoice_*` billing section becomes enabled/visible; on recheck the entire fieldset is disabled to prevent it being submitted.
3. On a future validated Woo checkout, map shipping-first `billing_*` into real **WC_Order shipping**. Set **WC_Order billing** only from genuine separate invoice inputs when selected, otherwise Woo's real original same address remains. The customer email and Mexico RFC remain unchanged.
4. Logged-in Save Address requires **explicit opt-in**; use Woo's own customer-update gate to stop unrequested updates. For opted-in customer, store shipping and separate billing into matching WC_Customer objects (Woo CRUD) without direct SQL or client storage. Next checkout Shipping prefill uses the saved **shipping** account address, not saved invoice.
5. Real validation uses live Woo country locale `WC()->countries->get_address_fields()`, including Germany optional state and UAE optional/hidden postcode; frontend uses existing `wc_address_i18n_params.locale` for matching rules. Do not hardcode USA ZIP/state required for all destinations.

## 3. Evidence currently PASSED (NO business writes)
- PHP `php -l` for new contract, Checkout include and template PASS; `node --check` native checkout JS PASS.
- Exact GitHub source PHP Woo stub tests **43 assertions PASS**: guest cannot save despite forged control, logged opt-in true/false, order shipping vs independent billing, customer metadata mapping, per-country invalid/missing fields, Germany optional state, UAE optional postcode, shipping account prefill. **This does NOT exercise a real WC_Order save.**
- Python source/markup contract **41 assertions PASS**.
- Real Dev anonymous Checkout with candidate JS/CSS response substituted only inside isolated Chrome and **synthetic Woo-like invoice markup** (new PHP not deployed): 390px, 320px, 1440px each displays disabled guest save, checked same-billing, independently expanding/disabling Billing, one form, zero horizontal overflow, zero JS errors.
- Same three viewports: Woo native `wc_address_i18n_params.locale` respected: Germany State optional; UAE State optional and Postal code hidden/not required. Synthetic fixture initially omitted Woo field wrapper ids; corrected fixture and **3/3 passed**, no runtime source change caused by that harness error.
- Real Dev Woo country locale independently queried (read-only) for US, MX, DE, GB, HK, AE, JP, EC.

## 4. NOT YET ACCEPTED / business write boundary
**Do not deploy this branch or run any checkout/order/customer write** under previous Newsletter UI Canary approval. This PHP alters real order addresses AND registered customer's future stored addresses. It requires a new, exact-head **Dev-only address/data-contract validation approval**.

Before that, independently verify with a true native Woo rendering of the candidate PHP (not just fixture), including billing field country/state dropdown and full serialization, and test checkout without a real gateway in a separate synthetic isolated database. Never create a real order, write actual customer meta, charge card or send email without separate explicit approval. Validate alternate billing across at least US/MX/DE/AE; preserve Woo native shipping cost & taxes and Mexico RFC. Ensure email changes after Newsletter pending requests require new consent, already covered by its independent branch.

## 5. Future Dev release sequence (if separately approved)
1. Refresh exact PR #140 HEAD and GitHub source SHA; confirm five-file allowlist only.
2. Record WordPress dev-only identity and exact live four old SHA/mode/owner; new PHP must be absent. On mismatch STOP.
3. Off-webroot (700 directory) SHA-verify backups of **four existing files**; prepare guarded reverse rollback.
4. Stage exact five candidate blobs outside webroot; lint 3 PHP+1 JS and SHA.
5. Atomic-per-file order: **new address contract PHP → CSS → JS → form Checkout template → inc/checkout.php LAST**. On error rollback in reverse, immediately restoring `inc/checkout.php` before deleting new PHP. No DB writes or option changes.
6. Post file hashes, modes, homepage/Cart/Checkout HTTP health, 320/390/1440 browser UAT, Billing state/postcode locale, real shipping rates, MX RFC, Newsletter OFF, Shipping↔Payment user inputs. Observe no checkout/order/payment/network POST beyond guest test cart + Woo review.
7. Full persisted-order and account-address UAT remains a **distinct gated step**, not falsely marked PASS after file deployment. GitHub PR merge and WWW rollout require later separate Owner review and authorization.

## 6. Remaining reference gaps
Carrier ETA labels need verified service data; Newsletter real subscription remains OFF and requires separate confirmation-mail permission; intermittent 2-second Country/Province lag #132 separately unresolved. Physical Owner annotated screenshot 1:1 UAT not yet done.
