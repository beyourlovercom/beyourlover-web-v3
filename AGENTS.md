# BYL Website V3 — Agent Governance V2
> Applies to this repository and the new BeYourLover website only. The Owner-approved operating model is Dev-first. Do not inherit a generic Local-first mandate from shared-plugin repositories.

## Mission and source of truth
- Build **the complete new website** on `https://dev.beyourlover.com`; tune theme, plugins, content, integrations and business flows together until full Owner UAT passes.
- **GitHub is the only code source of truth.** Work on a scoped branch/worktree; no untracked or direct Dev-only source fixes. Record exact commit SHA for deployed code.
- `localdev.beyourlover.com` is an optional isolation/risk sandbox, not a compulsory gate for every BYL change.
- Current `www.beyourlover.com` is the live legacy store. **Never** deploy, mutate, retire a plugin, change routing/DNS or migrate data there without a separate explicit Owner authorization.
- Launch is a **full Dev-to-WWW cutover project**, not incremental feature deployment onto old WWW. Cutover needs its own plan, data delta synchronization, payment/SEO/ERP checks and tested rollback.

## Top-level mandatory standard — BYL Native-First Development & Dual UAT V1

**Applies to all BYL Website V3 development agents, coding tasks, reviews, UI modules and release plans.** It is a release/closure gate, not a visual preference or Checkout-only recommendation. Read the [canonical Native-First & Dual UAT standard](docs/agent/NATIVE-FIRST-DUAL-UAT-V1.md) before implementing or accepting an interactive surface.

1. **Freeze an Owner-approved design/interaction baseline first.** Materially new or changed visual behavior still needs Owner design proposal PASS; do not keep redesigning approved pages while their underlying functions are broken.
2. **Native-first ownership and functional contract.** Before styling a control, map it to the existing WordPress/WooCommerce/Andy Commerce/Andy Core authority, native field/API, validation, events, state, failures and fallback. Reuse genuine controls and data; theme/drawer CSS or JavaScript must not shadow, clone or replace them without an approved architecture change.
3. **Inventory every in-scope interactive control.** For each input, button, selection, notice and link: prove focus/click/type/search, real authority and state changes, errors/keyboard/touch, mobile/desktop and native fallback. Track UNTESTED / SOURCE-ONLY / ISOLATED PASS / LIVE DEV PASS / FAIL / BLOCKED separately; synthetic screenshots and mocked tests are never live PASS.
4. **Functional gate before visual polish.** Fix P0/P1 native functionality failures before fine visual calibration; a beautiful page with an unusable Woo field is **FAIL**. Then polish against the frozen Owner reference, run affected functional regressions again, and obtain **both Functional PASS and Visual Owner PASS** before page closure.
5. **Page-by-page, risk-scaled acceptance.** Interactive commerce uses **Cart → Shipping → Payment**, with shipping methods inside Shipping and totals/confirmation within Payment; **no additional Delivery/Review customer stage** without explicit Owner approval. Checkout's [54-case control inventory](https://github.com/beyourlovercom/beyourlover-web-v3/issues/125) is the current contract (Cart 10 / Shipping 22 / Payment 12 / cross-flow 10). Static/content pages require proportionate checks, not payment tests.
6. **Never fake capability or acceptance.** Actual Woo cart/session/shipping/tax/discount/gateway behavior is authoritative. If a native widget or gateway is blocked, report FAIL/BLOCKED, preserve the legitimate fallback, fix the integration and re-test; do not use hardcoded prices, mock payment buttons, hidden validation, or unapproved real transactions to fabricate PASS.
7. **Maintain existing release and safety gates.** Owner functional/visual signoff is distinct from lint, isolated DOM tests, CI, Dev rollout and WWW permission. Each exact-head Dev deployment still needs applicable authorization, verified SHA, backup and rollback; no main merge, WWW, real order/payment/customer/DB/mail write without its separate gate.

## Top-level mandatory standard — Mobile Performance Release Gate V1

**Applies to customer-visible BYL V3 pages, shared frontend assets, and pre-release Dev validation.** Follow [Performance Release Gate V1](docs/agent/PERFORMANCE-RELEASE-GATE-V1.md) in addition to Native-First Dual UAT. This is a separate, mandatory release/page-closure decision, not a replacement for functional or visual UAT.

- Public mobile candidates: three authentic Lighthouse mobile runs against the **installed exact-SHA Dev candidate**; median **Performance >=90, LCP <=2.5s, FCP <=1.8s, TBT <=200ms, CLS <=0.1**, with no single Performance run below 85. Record reports and environment/config; source-only CI does not count as a live performance PASS.
- Test impacted page types and critical shared-template paths proportionately. Global frontend assets must cover affected home/PDP/Cart/Checkout/active paid landing pages as applicable; do not omit checkout or attribution flows to improve the score.
- Andy plugin **admin backends are Desktop-only** and exempt from mobile Lighthouse; customer-facing cart, inquiry, checkout, marketing, product, blog and site layouts are **not exempt**.
- A `PERF FAIL` or `PERF BLOCKED` prevents release-ready/page closure/WWW cutover. Only an explicit time-bounded Owner exception for the specific candidate can override the *performance* gate; it is **not PERF PASS** and never waives payment, security, native function, real-transaction or Production authorization gates.
- Run the offline checker `scripts/performance_gate.py` on genuine Dev Lighthouse JSON, retain non-sensitive evidence, and preserve the existing Dev-backup/rollback and WWW hard stops. No paid service is mandated.

## Autonomous execution inside an approved work package
- Once the Owner approves a bounded objective/design, investigate relevant code and docs, implement, run proportionate tests, fix failures, commit, push, prepare/update a PR, and run safe Dev validation without asking permission for each routine step.
- Make reasonable implementation decisions within approved behavior. Do not silently widen product scope, change approved UI, business rules, permission models, payment routing or data ownership.
- For existing approved visual designs, implement and test them. For **new or materially changed** UI/interaction, obtain visual proposal PASS first. For all Owner-facing UI, real **Dev Owner UAT PASS** remains a closure gate.
- One work package → one isolated branch/PR; one active implementation provider per worktree. No simultaneous edits to the same file; rebase/refresh on latest authoritative branch before sequential work.
- Relevant tests must pass, not merely run. Failed commands, missing CI, partial evidence and absent gateway capability must be described accurately; do not invent PASS.

## Non-negotiable stops
Stop and ask the Owner before:
1. Any WWW/Production release or cutover, including live plugin enable/disable and customer-visible production changes.
2. Real payment transactions, payment-routing activation, sending transactional email externally, or external-account/secret changes beyond a previously explicit test gate.
3. Destructive/irreversible actions or bulk mutations of actual customer, Woo order, affiliate or canonical business data.
4. Materially new architecture, UI, business behavior, authorization/role change or an unresolved decision outside the approved work package.
5. Owner visual design PASS, live Owner UAT PASS, login/2FA/secret/account-owner actions the agent cannot perform legitimately.

## Engineering completion contract
- Preserve WooCommerce as checkout, tax/shipping/payment/order authority; Andy Commerce is business policy; BYL V3 Theme is rendering/interaction; Andy Core is generic foundation.
- No bypass of real Woo validation, checkout nonce, authorization checks, payment confirmation, ERP fulfillment gates or discount restrictions.
- Before modifying live Dev runtime: confirm scope and source SHA, back up affected files/config, use additive/reversible changes where possible, and verify real browser/server behavior. Don't claim Dev PASS on lint alone.
- Mobile-first. Test changed UI at phone (~390px) and desktop (~1440px); use the approved visual baseline and meaningful checkout states; no artificial Woo orders to fabricate success.
- Never commit secrets, full WordPress copies, credentials, uploads, databases, caches, or temporary screenshots. Avoid restarting a working computer/browser/DC service merely to run tests.
- The final task report states exactly what changed, SHA/PR, verification, unresolved issues, and whether WWW was touched.

## Read context only when relevant
- [Architecture and module ownership](docs/agent/ARCHITECTURE.md) — cross-module or plugin/theme changes
- [BYL Theme OS V1](docs/agent/THEME-OS-V1.md) — Theme module ownership, SemVer, ZIP receipts, upgrade and rollback
- [Dev environment and integration](docs/agent/DEV-ENV.md) — local/Dev work, deployments, branch handoff
- [Cart, Checkout, payment contracts](docs/agent/COMMERCE.md) — commerce/customer-funnel work
- [Native-First Development & Dual UAT V1](docs/agent/NATIVE-FIRST-DUAL-UAT-V1.md) — mandatory ownership, per-control function gates, visual regression and dual PASS
- [Testing and Owner UAT](docs/agent/TESTING.md) — test selection, browser evidence, closure criteria
- [Security and data boundaries](docs/agent/SECURITY.md) — auth, PII, admin, payments, secrets
- [Release and Dev-to-WWW cutover](docs/agent/RELEASE.md) — staging/release/cutover planning

Read only the needed files and related source/test code. Existing feature docs and accepted UAT contracts override guesses; on conflict, flag it rather than silently changing a frozen decision.
