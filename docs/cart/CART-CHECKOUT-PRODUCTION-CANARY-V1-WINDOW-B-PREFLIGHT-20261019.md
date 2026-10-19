# Cart + Checkout Production Canary V1 — Window B source preflight and release contract
**Authority:** [Issue #146](https://github.com/beyourlovercom/beyourlover-web-v3/issues/146). **Incident owner:** [Issue #145](https://github.com/beyourlovercom/beyourlover-web-v3/issues/145). **Commerce E2E:** [Issue #87](https://github.com/beyourlovercom/beyourlover-web-v3/issues/87). **Prepared:** 2026-10-19. **Baseline main:** `c7b0c067f56f26279ab2987e26276f4707c1b16c`. This is a **source-review-only** contract; runtime readiness, incident recovery, and production deployment are **NOT established**.

## 2026-10-19 Window A authenticated WWW inventory — updated compatibility gate
Window A reports an authenticated, **read-only** WWW plugin inventory: WooCommerce **10.9.4** ACTIVE, CommerceGurus CommerceKit **2.4.2** ACTIVE (update required, not authorized), WP Rocket **3.23.1** ACTIVE, Redis Object Cache **2.6.5** ACTIVE, Andy Core **1.5.8** ACTIVE, WooPayments **10.9.0** ACTIVE and Checkout Plugins – Stripe ACTIVE. This is an evidence snapshot, **not a green integration signal**.

**Cart incident findings:** Shoptimizer `#shoptimizerCartDrawer` shell is present, but Woo `.widget_shopping_cart_content` remains empty; `wc-cart-fragments` script absent and `wc_cart_fragments_params` undefined. Same result on ordinary WWW page and `?nowprocket` optimization-bypass A/B. Window A read actual WP Rocket settings: Delay JS, Defer JS, JS minify/combine, CSS minify and Optimize CSS Delivery all OFF. Do **not** blame or globally deactivate WP Rocket. Root cause of enqueue omission still under investigation; a necessary targeted WWW hotfix is separately Owner-approved **for Window A only**. Do not declare it fixed until actual recovery evidence is recorded. /cart slowness, 502 and USD order baseline remain separate unresolved investigations.

**Fundamental architectural difference:** WWW currently renders a **Shoptimizer-derived cart shell** while Dev Cart V2 uses standalone BYL V3 theme. See existing `docs/G4.5-SHOPTIMIZER-DEPENDENCY-AUDIT-LITE.md`: localdev legacy active stylesheet `shoptimizer-child-theme`, parent `shoptimizer`, child theme has significant business hooks/templates and stored Woo/product/customizer dependencies, with G12/G13 cutover and G14 retirement gates. These figures describe that prior **localdev** audit; do **not** claim actual WWW theme/child versions or deployed file hashes until Window A confirms them.

**Critical release design correction:** A successful V3 Dev Cart cannot be copied as-is into WWW Shoptimizer. `page-cart.php` calls V3-only header/footer/helper functions; Drawer JS targets V3 shell `a.byl-shell-cart`, whereas WWW uses `#shoptimizerCartDrawer`; V3 assets and hooks aren't automatically installed by a different active theme. Consequently:
- Reject any whole-theme replacement, front-page/product/checkout edits, blanket script enqueue, or bulk Dev file copy as a "Cart-only Canary".
- Before choosing implementation, inspect exact active WWW parent+child theme, Woo cart page **classic shortcode vs Cart Block**, actual WordPress template route/page ID, Shoptimizer drawer trigger and callback chain, Woo fragments, child-theme overrides and installed Andy Core/Commerce integration. Record file hashes/mtimes and conflicts from A's approved hotfix.
- **Design option (not yet selected):** a narrowly scoped, version-pinned **compatibility adapter in an already approved production extension point** (existing child-theme hook or approved Andy Core/Commerce integration), which can render BYL Cart V2 only to authenticated allowlisted staff. This must not require switching global `template`/`stylesheet` or depend on Shoptimizer private DOM for the permanent V3 architecture. Do not assume installed Andy Core 1.5.8 already provides such a hook; confirm it. Any installer/plugin/update is a separate WWW approval.
- A separate `/cart/` template-selection path is required: when OFF, use WWW's existing Woo/Block cart page; when ON for one staff user, use an adapted V3 design without unverified V3-only header functions. **Never** route a guest through staff markup or poison Cloudflare/Hostinger cache.
- A separate Drawer path is required: when OFF, Shoptimizer legacy Drawer continues (after A restores it); when ON for staff, use new Drawer without double intercepting clicks, double Woo fragment requests or stale cart sessions. Preserve old cart link and independently reversible selector.
- No customer-traffic experiment until staff-after-guest negative cache check, Woo cart/store API session+nonce test, BYL49/Woo taxes and shipping parity, real checkout navigation, p95, 5xx and PSP baseline are substantiated.
- A's authorized **emergency correction must complete and be preserved** before B can seek authorization for *any* WWW release. B must compare post-fix WWW hashes against GitHub/current deployed source and lock exact file allowlist, code SHA and rollback record. GitHub main commit alone is not a production binary receipt.

**Window B immediate deliverables, no WWW write:** (1) this compatibility gate, (2) source-only regression evidence + two separate fail-closed, currently unwired internal eligibility flags (Draft PR #148), (3) exact runtime field/hook inventory checklist for A-to-B handoff, (4) design review of future compatibility adapter after A returns the production baseline. **Owner's A hotfix approval is not approval for B runtime activation.**

## P0 partial recovery and post-upgrade gate — 2026-10-19, from A #145
**Owner-confirmed minimum source fix performed, NOT an all-guest stability closure.** Window A's [production fix evidence](https://github.com/beyourlovercom/beyourlover-web-v3/issues/145#issuecomment-6259132037) confirms the owner manually removed two end-of-file PHP hooks that `wp_dequeue_script` / `wp_deregister_script` `wc-cart-fragments` on non-cart and non-checkout pages, and prevented `woocommerce_get_script_data` for that handle. A post-edit editor re-read verified removal. Original exact backup stored locally by A (not to be published). On authenticated browser `/?nowprocket` the legacy mini-cart briefly populated (~17 KB); this is evidence of a repaired source-level root cause, **not** proof of stable public fragments.

Subsequent [A guest/backend findings](https://github.com/beyourlovercom/beyourlover-web-v3/issues/145#issuecomment-6259379009) show: after an unrelated WooCommerce/WooPayments update attempt, maintenance HTTP 503 occurred and later fresh anonymous mini-cart remained intermittently white; one Woo frontend fragment fetch took approximately its 5000 ms request timeout (status 0), while separate manual `?wc-ajax=get_refreshed_fragments` could return HTTP 200 with fragments. `wp-admin/admin-ajax.php` returned nginx HTTP 503. Authenticated/regular Chrome and Safari showing a cached/warm Drawer is **not** a clean anonymous cold-start UAT PASS. A's latest [Owner observation](https://github.com/beyourlovercom/beyourlover-web-v3/issues/145#issuecomment-6259437674) confirms ordinary browsers work while private/incognito remains suspect. A also observed WooCommerce **11.2.1** and WooPayments **11.2.0** after the updater incident; reconcile with old plugin audit of 10.9.4 / 10.9.0.

**Window B dependency update:** A's minimum code-fix milestone is complete, so B may continue **unwired GitHub-only compatibility programming**. For the actual WWW internal canary, all remain **HOLD** until A supplies:
- cold fresh guest, normal/private, empty and filled Cart Drawer UAT, at M14 and desktop, including true Woo frontend request status/timings under the configured timeout;
- origin/PHP-FPM/admin-ajax 503 recovery and stable performance readings; distinguish slow `/cart/` from legacy drawer;
- post-upgrade WooCommerce/WooPayments plugin file integrity/versions and read-only gateway availability, not live payment trials;
- full WWW hotfix before/after file fingerprints, rollback confirmation and known-good `functions.php` receipt (sensitive local backup must never be published);
- clearly labeled baseline USD completed/processing orders and decline/failed order trends without assuming reported gaps are PSP failures.

**In-progress code safeguard:** [Draft PR #148](https://github.com/beyourlovercom/beyourlover-web-v3/pull/148) now separately rejects eligibility without six trusted, server-attested booleans: anonymous cold empty/filled cart PASS, legacy fragments completing within timeout, stable dynamic AJAX with no unresolved 503, post-Woo-upgrade integrity verification and gateway read-only health verification. No real WordPress adapter or WWW hooks are installed, so these are **future integration requirements**, not observed operational truths. Do not set these flags `true` merely because the manual source hotfix was applied.

## 0. Single-owner coordination and permission
- Window A / #145: only owner of current WWW /cart/ latency, blank Cart Drawer, intermittent 502, payment/order baseline and approved emergency repair. It must supply its latest confirmed findings/hotfix manifest; a CommerceKit wishlist 204 JSON error is identified but **has not been causally linked to a blank cart**.
- Window B / #146: only owner of **new** Cart Drawer V2 and focused native Woo /cart/ page release preparation, dual kill switches, source/Dev UAT, later internal production Canary proposals. Do **not** pursue parallel fixes to current WWW cart and do **not** deploy while #145 or #87 is HOLD.
- Before any future WWW change, reconcile actual running theme, active plugins, installed file hashes, cart/checkout routes, PHP hooks and cache behavior against GitHub `main` **after** Window A releases any hotfix. Stop if active WWW theme is not the expected source or if any overlapping changes are unexplained.
- Project initiation or a Dev Owner `pass` is **not** WWW permission. Require separately authorized production exact file allowlist/commit, backup, rollback, monitoring and Owner gate at *each* stage. No real transaction, payment, customer/subscriber DB or email action; never restart READY devices.

## 1. What actually exists in the GitHub main baseline
| Asset or contract | Source at this SHA | Risk / integration fact |
| --- | --- | --- |
| Drawer PHP and hooks | `theme/beyourlover-v3/inc/cart-drawer.php` (blob `8c9c9731eb8d50947401a1450255cd097aef2a38`) | `byl_v3_cart_drawer_runtime_enabled()` **requires** host `dev.beyourlover.com` and WP environment `staging`. WWW intentionally OFF. Drawer JS/CSS and footer are gated by this. |
| Drawer JS | `assets/js/cart-drawer.js` (`22d147fa70cb80058f5de71edbcd6a276fe3e4be`) | Intercepts header cart links only if a gated Drawer root exists; uses Woo Store API session, coupon/quantity mutations, and safe fallback `config.cartUrl`. Never execute against real WWW customers without independent testing. |
| Drawer CSS | `assets/css/cart-drawer.css` (`b5dbd036527ce50dd8777a8628ac92d0503e2376`) | Dev-oriented styles and a Dev preview banner; needs separate production eligibility review. |
| Empty cart presentation | `inc/notification-cart.php`, `inc/notification-cart-empty.php`, `assets/css/cart-empty-parity.css` | `byl_v3_cart_empty_page_parity_enabled()` **depends on the Drawer runtime enable function**. Not independently switchable today. Dev empty cart also replaces Woo empty template and suppresses nested cross-sells, preserving Cart totals/checkout hooks. |
| Cart page | `page-cart.php`, `header-cart.php`, `footer-cart.php` | Custom focused page template uses native `[woocommerce_cart]`. WordPress theme/template routing determines if it is active. There is **no tested production cohort-specific route/fallback** here. |
| Cart page assets | `inc/theme-os-assets.php`, `assets/css/cart.css`, `assets/css/cart-focus.css`, `assets/js/cart.js`, `assets/js/cart-experience.js`, `inc/cart-experience.php` | `is_cart()` loads assets without a separate production Canary gate. Native Woo totals/rates/coupons must remain authoritative; Cart UI cannot be switched by assuming a CSS file alone. |
| Checkout | PR #143 + #144 already merged | Native `/checkout/`, not a Checkout Drawer. Must not be changed as part of Cart-first Canary. |
| Legacy production installation | **UNKNOWN / UNVERIFIED** | Existing production may use a different active theme/template and dynamic plugins. GitHub main is not evidence of deployed WWW bytes. |
| Dev deployed cart runtime | **UNKNOWN / NOT HASH-VERIFIED on this pass** | Remote Desktop Commander monthly usage blocked read-only SSH; no retry or reconnect. Prior Dev Owner PASS cannot substitute for current runtime fingerprints. |

## 2. Release order: independent, fail-closed flags
**R0 (now):** GitHub source/document preflight only. The existing production code path and all deployed bytes remain unchanged.

**R1 (later, requires separate code review and Dev UAT):** implement *two distinct, server-side, default-OFF* eligibility decisions, without overriding Woo cart sessions:
- `cart_drawer_internal_enabled`: applies only to an explicitly listed **authenticated internal staff identity** (allowlisted server-side user ID plus appropriate capability and logged-in auth). Must additionally verify exact canonical `www.beyourlover.com` host, `production` environment, expected installed active theme/adapter, release build ID, master kill switch ON, and Woo readiness.
- `cart_page_internal_enabled`: **separate** gate for `/cart/` page template/assets/empty state only, independent from Drawer. Public visitors remain on the *actual verified* legacy cart template. Source `page-cart.php` existence does not ensure switchability in the active WWW theme.
- Do not enable via public URL query (`?preview=1`), unsigned cookie, browser localStorage, JS-only boolean, user-agent, IP-only control or WordPress global public option; do not mistake a CDN cache key for authentication.
- Existing Dev preview query remains Dev-only and **must never activate a WWW cohort**; production should not emit the Dev preview banner.
- Ensure the server-side gating occurs **before** header/cart asset enqueue and template render, with consistent behavior across full page, fragments, Woo AJAX/REST and mini-cart. No hidden public JS interception; when OFF, the legacy cart link and page must continue to work.
- **CDN and WordPress cache isolation:** prove that internal personalized HTML is never publicly cached or served to guests; demonstrate bypass headers and Cloudflare/Hostinger exclusions with a guest-after-staff negative test. Calling `nocache_headers()` alone is not sufficient if an upstream CDN serves a stale copy.
- Keep distinct kill switches, version pinning and **known-good active production rollback**. Do not ship a theme/plugin swap before the live theme/route inventory and conflict audit. No active hotfix from Window A may be overwritten.
- Avoid enabling Andy Commerce M4/gifts/marketing/newsletter by association. Preserve true Woo free shipping and IOSS/tax and one coupon. No fake shipping/payment state.

**R2, internal Drawer only:** Owner authorizes narrow WWW file/config list after #145 is recovered, #87 E2E freshness review, gates implemented+tested, origin/access/cache evidence, exact CI & source SHA. Staff tests on M14 390 and desktop; public guests cannot receive Drawer root/assets/JS behavior. Existing `/cart/` fallback **unchanged**.

**R3, internal `/cart/` only:** separate Owner approval; isolated page route/template selection and empty cart parity gate; legacy Drawer/public page remain unaffected. Test non-empty/empty coupon, quantity, total, address-free and free-shipping with Woo real authority but **no order/payment submission**.

**R4, actual customer percentages:** P2 separate user authorization with observed paid-order and PSP baseline and tested cache/rollback; only then consider 1–5% Canary as a *proposal*, not a configured rollout. Cart and Checkout are never ramped together.

**R5, final:** Full staged release, paid-order conversion checks, independent switches kept available, separate Owner PASS. Checkout V3 follows Cart only after its own gateway/3DS and #87 gates.

## 3. Future live acceptance gate: evidence needed before GO
**All conditions required; missing evidence = HOLD:**
1. #145 explicit recovery/containment handoff: root-cause status, date, WWW modified files + before/after SHA, baseline paid USD orders, 502/5xx/TTFB, gateway health, owner decision.
2. #87 refreshed Dev Cart → Checkout → Payment → Thank You flow test; **no assumptions from visual UAT only**.
3. Exact WWW active theme/child theme/plugins/routes and file hashes; compare to current GitHub main, record divergence and preserve emergency fixes.
4. Exact implementation of two server-only OFF switches, staff allowlist + capability check, no direct URL/public cookie activation.
5. Evidence that old route/link still works with both switches OFF, guest after admin cache isolation, cross-session cart isolation, Woo Store API coupon/quantity/nonce flows, updated fragments and any extra plugin hooks.
6. Backups outside webroot, immutable release manifest + rollback commands verified on approved non-production test, both switches independently reverting without payment state changes.
7. WWW P0 error and performance alert thresholds chosen **from measured baseline**, not guessed; mobile M14 and desktop checks, precise stop/rollback trigger, direct incident Owner escalation.
8. Owner approves a specific release operation and allowlisted files with exact HEAD (new approval each R2/R3/R4); no standing unconditional WWW write authorization.

## 4. Immediate Window B backlog (GitHub-only until gates open)
- [x] Audit source hooks and dependencies at `main`=`c7b0c067f56f26279ab2987e26276f4707c1b16c`.
- [x] Identify coupling: Dev/staging Drawer gate; empty-cart parity linked to Drawer; page-cart auto-template; asset loading on is_cart; JS header cart interception; unsafe assumptions about WWW theme.
- [x] Draft isolated gate/route/cache/rollback contract with two independent switches, fail closed.
- [ ] Inspect actual Dev file hashes and complete Cart M14+desktop runtime UAT; access unavailable during this pass, do not pretend verified.
- [ ] Consume #145 repaired-WWW snapshot (and #87 E2E decision); perform read-only **WWW source/runtime conflict inventory once Owner Window A issues handoff**.
- [ ] Implement independently tested gated adapter and source-only regression in separate PR **after** exact active theme and CDN rules are known; no code deployment automatically.
- [ ] Prepare exact release checklist/monitoring GO/HOLD and request scoped Owner release approval.

## 5. Current objective outcome
**Status: PREFLIGHT SOURCE AUDIT PASS; PRODUCTION RELEASE NO-GO / HOLD.** This document is not a production release artifact or WWW authorization. A clean GitHub CI only validates code, not WWW revenue, payment or server state.

**Related:** [Issue #146](https://github.com/beyourlovercom/beyourlover-web-v3/issues/146), [Issue #145](https://github.com/beyourlovercom/beyourlover-web-v3/issues/145), [Issue #87](https://github.com/beyourlovercom/beyourlover-web-v3/issues/87), [PR #133](https://github.com/beyourlovercom/beyourlover-web-v3/pull/133), [PR #143](https://github.com/beyourlovercom/beyourlover-web-v3/pull/143), [PR #144](https://github.com/beyourlovercom/beyourlover-web-v3/pull/144).
