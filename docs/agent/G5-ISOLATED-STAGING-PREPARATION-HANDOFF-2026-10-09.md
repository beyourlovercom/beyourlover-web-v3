# BYL G5 — Independent Provider Test-Account Staging Handoff

Status: PREPARATION ONLY. This is NOT an operating public WooCommerce website, a cloned Dev site, a test account connection or permission to create orders.

## Authorized scope

Owner explicitly approved separate temporary staging preparation; **no AfterShip**, **no real customer/order data**, **no ERP sync**, **no WWW deploy**, **no Production DB migrations**, **no payment/test orders before isolation PASS and separate bounded UAT authorization**.

Source baseline: GitHub `beyourlovercom/beyourlover-web-v3` main commit `6ec004cb953856d289183b939f8e55e4d13619ac`, with G5 PR #44 exact Checkout JS blob `0144790568ca6305eea0096e75f186564de237a8` and G5 PR #48 Dev Safety MU blob `eaa62f7dd3ff1edaa39b7acf132d9998d975162c`. The new integrated G5 temporary branch includes combined offline regression tests, but is not a merge to main. It is a packaging/testing branch only.

## Hosting dependency (verified, not assumed)

Hostinger shared account `u247343654` has PHP 8.3, WP CLI and SSH. The existing Dev WordPress database account grants do **not** allow CREATE DATABASE, so no separate database can be prepared with the current Dev DB login. **Do not copy the existing wp-config.php, database, backups, uploads, payment credentials or customer/ERP data.** A new empty database and separate independent website must be provisioned through Hostinger hPanel.

Proposed permanent test-only hostname: `g5-uat.beyourlover.com` (one-level subdomain). Do not use a nested `g5-uat.dev.beyourlover.com`: Hostinger web/cloud subdomain support can differ. A Hostinger temporary domain may be used for preliminary CMS installation, but before payment Test Account onboarding, pin a stable canonical HTTPS hostname and rerun all guards. If Cloudflare hosts DNS, add only the new staging DNS record; do not edit WWW or existing Dev records.

Hostinger docs:
- https://www.hostinger.com/support/1583214-how-to-add-a-website-in-hostinger/
- https://www.hostinger.com/support/1583405-how-to-create-and-delete-subdomains-in-hostinger/

## 0. Before Hostinger GUI

- Use an **independent website**, separate document root, empty database and **new DB user**.
- Keep WordPress private, no public indexing. Obtain TLS before Jetpack/WooPayments onboarding; no anonymous public commerce.
- Never select Clone Website or migrate an existing WP site. Do not import any SQL/database or customer info. Create a new WordPress installation, unique site administrators and salts stored in Hostinger vault, not in GitHub/chat.
- Site URL and home both `https://g5-uat.beyourlover.com`, WP_ENVIRONMENT_TYPE `staging`. Never reuse `dev.beyourlover.com` WordPress DB credentials or WWW credentials.

## 1. Before installing WooCommerce/WooPayments

Install only the **isolated temporary safety** MU source `tools/g5-isolated/byl-g5-isolated-preparation-lock.php` into the new site's `wp-content/mu-plugins/byl-g5-isolated-preparation-lock.php`, with `php -l` and SHA verification. It is **not** for existing Dev or WWW and does not add a BYL commercial feature plugin.

At this preparation phase the MU must:
- close all Woo payment gateways and Woo classic order submission;
- block Store API Checkout and Woo REST order routes;
- deny native Woo webhooks, WordPress mail, all WordPress outbound HTTP;
- reject AfterShip legacy inbound URL paths;
- set noindex/noarchive and no-store.

**Do not weaken this gate to connect WCPay** without a separate reviewed change. It deliberately prevents WordPress.com/Test Account onboarding until the next gate.

## 2. Minimal test storefront install only after site/DB verified

Use a **fresh** WP + WooCommerce + WooPayments Test Account code installation; install BYL V3 Checkout theme runtime from the source package only after verifying all dependencies (Andy Core and Andy Commerce if needed). Do not install AfterShip, Affiliate, ERP/Woo connectors, webhooks, production email/SMTP plugins, production analytics/ad tags or additional business plugins.

Use a newly created synthetic account and synthetic non-stock product with non-identifying test shipping details only AFTER the next transaction/UAT authorization. Before that, no order, user-facing shop/payment action, or external service link is allowed.

WooPayments documentation states dedicated Test Accounts are supported for staging, and a fresh site must separately connect to WordPress.com for sandbox onboarding. Never copy Dev or WWW WooPayments saved options/account tokens or force Safe Mode:
- https://woocommerce.com/document/woopayments/testing-and-troubleshooting/test-accounts/
- https://woocommerce.com/document/woopayments/testing-and-troubleshooting/safe-mode/

## 3. Mandatory preparation acceptance — zero real data

| Check | Expected |
| --- | --- |
| Public hostname / TLS | Stable separate HTTPS and document root, never mapped onto existing Dev/WWW |
| Database / wp-config | Unique EMPTY DB, new user; DB name/user not equal existing Dev or WWW; **no live migration** |
| WP environment | staging and exact canonical home `https://g5-uat.beyourlover.com` |
| WordPress plugins | WooCommerce and WooPayments code only as needed; **AfterShip NOT installed**, no ERP/SMTP/ecommerce bridge |
| Woo/AfterShip API keys | **0**, no authorized external Provider order polling |
| Orders | **0** |
| Customer / uploads | No existing real customers, orders, imported media or credentials |
| Mail / webhook | Global preparation denies, 0 configured outgoing email/ERP endpoints |
| WordPress HTTP | Both `api.stripe.com` and `api.automizely.com` denied until approved test account gate |
| Woo gateways and Checkout APIs | 0 gateways; Store Checkout 403; no Place Order |
| CI / source SHA | New temporary branch Foundation CI PASS; exact file SHA deployment |
| Audit / rollback | Isolated off-webroot backups, never modify WWW or existing Dev |

## 4. Real Test Account / Provider UAT boundary — not authorized yet

After every zero-data/isolation check passes, **separate explicit bounded UAT authorization** is required to permit:
1. WordPress.com OAuth / new WooPayments **Test Account** onboarding solely on isolated test site.
2. Narrow outbound allowlist only to WooPayments Test Account and required WordPress.com sandbox services, while keeping AfterShip/Automizely/ERP/mail forbidden.
3. Synthetic test customer/product, a max per-window order budget of **3**, G5-01..08 cases and normal human-controlled 3DS challenge steps.
4. Transaction readback of Woo order, Test Mode intent, failure and duplicate attempt counts, no notifications/fulfillment.
5. Immediate UI Revoke + independent closed-gate verification before release.

Current Dev G2 stays CLOSED. No copied Dev Sandbox cookie/token/nonce. PR #44/#48 remain Draft unless separately authorized for merge.

## 5. Known limitation / next owner step

Hostinger independent website + empty database require hPanel authorization; neither the existing SSH user nor the WordPress database user can provision a Hostinger website/vhost and new database by themselves. If hPanel session is unavailable, **stop there** and request only the minimal Hostinger GUI step (create blank independent WordPress website), rather than pretending a local directory is a working public staging site.
