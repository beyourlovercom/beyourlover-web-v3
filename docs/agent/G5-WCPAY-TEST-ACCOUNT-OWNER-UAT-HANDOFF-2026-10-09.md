# G5 Isolated WooPayments Test Account — Owner UAT Handoff
2026-10-09 (Dev-only; source of truth GitHub)

## 0. Resume here; do not redo completed stages

**Site:** https://g5-uat.beyourlover.com — independently provisioned blank Hostinger site (unique MySQL DB `u247343654_n3Hkr`), distinct from dev.beyourlover.com and WWW. HTTPS valid. `WP_ENVIRONMENT_TYPE=staging`; WordPress `home` and `siteurl` canonical HTTPS. Default robots noindex, WordPress `blog_public=0`. No AfterShip plugin, Woo API keys, customer orders, products or ERP connection. Verified at last read: 0 orders, 0 products, 0 Woo API keys, 1 fresh WP admin.

**Code:** WooCommerce 11.2.1 ACTIVE; WooPayments 11.2.0 ACTIVE (plugin code only, **NOT connected** to WordPress.com/Test Account); BYL V3 theme ACTIVE with G5 PR #44 submit-lock JS. Guest Store API Checkout / Woo REST Orders / AfterShip legacy API HTTP 403, ordinary Cart API HTTP 200, HTTPS valid. Global isolated MU denies Woo card gateways, native checkout, email, native Woo outgoing webhook and all WP-origin HTTP while not in a controlled OAuth window.

**Visual:** Chrome CDP computed layout PASS at widths **320, 375, 390 and 1440**, no horizontal overflow, hero headline and body inside viewport, latest CSS loaded, no JS exceptions. 390px CSS Hero-only correction on Draft PR #51 (no WWW/Dev theme release). Distinguish technical UAT from provider payment UAT.

**GitHub:** PR #51 `g5/isolated-provider-uat-runtime-20261009`, exact-head prior to this doc `f876ded258ef90933555e3039d14721dc6c0ffaf`; Foundation CI run **38017838053 SUCCESS**. Additional Draft PRs #44 (Checkout) and #48 (Dev outbound) remain unmerged; main unchanged.

**Isolated MU:** Source `tools/g5-isolated/byl-g5-isolated-preparation-lock.php`, Git Blob `6bd616581673132e6861f9567f7c10e66f279ceb`, live isolated-site SHA-256 `c652e842612d311a73fd7dc8cc189b3a92595f1285a111d1ccf10341ef0996f3`. Original previous file retained off-webroot in `/home/u247343654/g5-isolated-uat-prep-20261009/byl-g5-isolated-before-connect-only-r3.php`. Deployed after hash/lint verification with rollback; **default remains DENY**. CI/Windows tests `scripts/test_g5_wcpay_connect_only.php` **20/20 PASS**, `scripts/test_g5_isolated_preparation_lock.php` **16/16 PASS**, no actual HTTP/transactions.

## 1. WordPress.com OAuth gate — currently STOPPED/REVOKED

WooPayments admin UI showed **Connect with WordPress.com**; this is required even for a Test Account. Official source: https://woocommerce.com/document/woopayments/testing-and-troubleshooting/test-accounts/ . Because `WP_ENVIRONMENT_TYPE=staging`, WooPayments should offer only **Test payments**, not a live account (verify visually, never assume). Never select **Start accepting payments** or upgrade to a live merchant account.

The isolated MU supports a **connect-only 30–60 minute time-bound allowlist** for HTTPS WordPress.com OAuth hosts **public-api.wordpress.com**, **wordpress.com**, **api.wordpress.com**. It is OFF by default; two wp-config constants must be deliberately set by the operator and timestamp must be within 1h:
- `BYL_G5_TEST_ACCOUNT_CONNECT_ONLY === true`
- `BYL_G5_TEST_ACCOUNT_CONNECT_UNTIL` integer Unix seconds greater than now and at most one hour ahead.

Even in this limited window: all Woo payment gateways, Checkout API, native order submit, Woo REST Orders, AfterShip API ingress, mail, Woo webhooks, Automizely, Stripe and unrelated WP HTTP are **DENIED**. The operator previously opened this **30-minute allowlist**, discovered the China Mac Chrome was **not running**, and **immediately deleted both constants**. Latest read-only runtime showed gateway DENY, Stripe/Automizely HTTP DENY, Woo Checkout/Orders 403, mail/webhooks BLOCKED. **No WordPress.com consent/connection occurred, no orders/charges.**

**One human prerequisite:** Owner reopens their existing Chrome normally on the China Mac and signs into their intended WordPress.com account as needed; do not restart Chrome by remote command, clone browser credentials or bypass login. Owner says `done` only when the browser is available. Operator then read-only confirms the normal Hostinger hPanel WordPress admin SSO session, reopens only the 30-minute connect-only window, navigates through **WooCommerce → Payments → Complete setup → Connect with WordPress.com**. User individually reviews OAuth identity/scopes and selects **Test payments**. Operator must not silently select an existing live merchant account, handle passwords or approve unexpected paid services.

Immediately after OAuth, remove both connect-only constants, re-run runtime guard & inspect only **non-secret Test Account flags**. If not connected before expiry, remain STOPPED and revoke; do not loosen allowlist.

## 2. Real G5 Provider UAT — separate later authorization

**No new order creation permitted yet.** The owner's blanket authority to proceed with routine development is not evidence of an explicit provider transaction budget. Once Test Account connection is confirmed:
- Verify isolated site still has **0 actual customer/order data**, zero AfterShip/ERP, mail blocked, Woo native webhooks blocked, no live account, provider=Test Account.
- Create only clearly marked synthetic customer/product and open a **narrow transaction window** after Owner specifies named G5-01..08 scenarios and maximum **3 additional synthetic test orders**. Check provider mode and remaining cap before each attempt; keep ordinary Dev G2 CLOSED.
- Run declined card, 3DS success, 3DS cancel, explicit retry, double-click pending, idempotency/event evidence, downstream isolation, revoke/teardown. Mock or failed Guest checks are not provider PASS.
- No PHP overrides that fake a Test Account, no cookie/nonce forgery, no live card, no WWW/ERP Production, DB migration, mail or AfterShip provider settings, no raw order POST replay.
- STOP and revoke on unknown payment status, external notification, unexpected ERP/fulfillment effect, gateway mode uncertainty or unauthorized order.
- PR merges remain separate exact-head gates.

## 3. Latest requested next action
The technical work has reached the Owner WordPress.com consent step. Wait only for normal Chrome + WordPress.com login availability; do not restart READY devices. Resume directly at Section 1, not from website/database/theme setup.
