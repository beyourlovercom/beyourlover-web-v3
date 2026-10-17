# Checkout Newsletter Opt-in V1 — Dev-only gated handoff
Date: 2026-10-17

## 0. Status and scope
- Status: **SOURCE + OFFLINE tests PASS; NOT DEPLOYED to Dev or WWW.** Do not represent the disabled markup or mocks as a live working mailing-list signup.
- Branch: `feature/checkout-real-newsletter-optin-v1-20261017`. Canonical code source is this branch's exact commit HEAD (obtain dynamically before any future release).
- Depends on PR #139 Mexico RFC, currently installed Dev-only. The source branch does not replace/undo earlier Shipping/Checkout modifications. Do not merge unapproved Draft PRs or change GitHub main.
- **Two separate approvals**: (a) Dev-only theme code Canary, with Newsletter code/site/outbound flags remaining OFF; (b) later explicit separate authorization for any real subscriber/confirmation email activity. Approval (a) must never implicitly enable (b).

## 1. Exact Dev preflight observed 2026-10-17
Theme: `/home/u247343654/domains/dev.beyourlover.com/public_html/wp-content/themes/beyourlover-v3/`.

| Runtime file | Currently deployed Dev SHA256 | Candidate code SHA256 |
|---|---|---|
| `woocommerce/checkout/form-checkout.php` | `b1194b1a653d3789665acf23f9c6a6db8466bc78396f9876580f3e6ef875ad02` | `1f4544ce5baedb2189e08e4a652ad3cdfc33d175a4672950b9ddc1a22792d392` |
| `inc/theme-os-assets.php` | `352940b6575148d8938e42779378378691ba77dad354084c907d9900748a0b2f` | `05ba95d20b8d41dd284c5429022f2e06858edbdf8a1e576c81cb4092a579d618` |
| `assets/css/checkout.css` | `b4782a2bdfe75774b2d1b06ca1990cd08135734a712b3844b3d5082823126c34` | `88e1ef5322db11bb67958f74e97c9e01b3f5623932a6b6eae257ca83cc77f362` |
| **NEW** `assets/js/checkout-newsletter.js` | **ABSENT** | `0bdc05cc9e08d8fb5d55497cbd6c1d8aac413c032e769c304893a135e9694d90` |

All three existing files were regular files at mode 644. The new script URL was HTTP 404 (expected when absent). Verified Dev options `yby_newsletter_enabled` and `yby_newsletter_outbound_enabled` are absent/OFF; **do not set them** during UI Canary.

## 2. Code and business truth
- Native Checkout contact card contains an **optional, initially unchecked** marketing checkbox matching Owner visual. PHP renders it disabled unless the installed `YBY_Newsletter_REST_Controller::subscribe_ready()` reports true. Current Dev is NOT ready, so the disabled checkbox and explanatory message are accurate.
- Andy Core REST `/wp-json/andy-core/v1/newsletter/subscribe` requires explicit consent and policy version, valid email, rate checks, state store + sender readiness; it can only begin PENDING state. Customer is NOT subscribed without email token confirmation.
- Checkout-specific JS runs separately from native Woo order submit; no Woo order field `name`, no local/session storage, no implicit subscriber creation. Uses same-origin only, credentials omitted and referrer disabled. No requests if unchecked / email empty / invalid. Errors uncheck without blocking checkout.
- Privacy hardening: after one requested confirmation, changing the email clears checked consent; a different address requires another explicit checkbox click. Changing email in flight does not misattribute the prior response or submit to the new address automatically.

## 3. Tests and remaining hold
- Actual exact GitHub code sourced through GitHub API; PHP lint for template and asset loader PASS, JS `node --check` PASS.
- Python source contract **37/37 PASS**.
- Node VM event tests **38/38 PASS** on exact Checkout JS (no browser network), including unchecked/OFF/invalid email, explicit first request, pending-only acknowledgment, 503 fail closed, same-origin, changed-email reconfirmation and in-flight change race.
- Earlier isolated Dev browser with injected OFF fixture: 390 / 320 / 1440 px disabled, unchecked, 1 Woo checkout form, 0 overflow or JS page errors and no REST writes. This is **not a live Dev deployment**.
- Full browser ready-state mock remains **HOLD** following browser environment `ERR_BLOCKED_BY_ADMINISTRATOR`; never bypass controls by using another computer/agent/URL. Code-only tests cannot replace full UI-ready browser UAT. Do not mark Final Newsletter PASS.

## 4. Approved Dev-only release procedure if a new exact-scope authorization is given
1. Verify code HEAD, all candidate SHA values above and Dev existing before SHA/owner/mode. STOP on drift.
2. Back up all **three existing files** outside website root (700 directory; SHA-verified). Verify the new JS does not exist. Keep rollback manifest and receipts.
3. Prepare **four** exact blobs from GitHub HEAD; lint PHP and Node. Do not stage or write to public WWW.
4. On approved trusted path, deploy new JS first, CSS, PHP asset loader, and LAST the Checkout template so the new checkbox never appears before its script. Use per-file atomic replacement with SHA checks and rollback on failure. Retain owner and mode 644.
5. Verify both standard/cache-busted public JS+CSS SHA, PHP lint, Dev homepage/Cart/Checkout HTTP health and one Woo Checkout form. Confirm checkbox disabled/unchecked on real Dev since mail OFF; do not trigger subscriber POSTs.
6. Recheck country/state/scroll, MX RFC, Woo real shipping amount/summary and Shipping -> Payment -> Shipping under isolated guest test cart with all checkout/payment POSTs blocked.
7. If any display/health issue, restore template first, then asset loader, CSS and remove new JS; verify original SHAs. No promises of 100% reference fidelity before physical Owner iPhone UAT.

## 5. Separate future business gates
- Real Newsletter activation requires Owner permission to enable Andy Core API/outbound flags, valid mail transport readiness, real confirmation email and subscriber data writes. Not in this scope.
- Other reference gaps: true guest Save Address/account flows, independent billing, verified carrier ETA, occasional Country/Province 2-second lag. They are NOT fixed here.
