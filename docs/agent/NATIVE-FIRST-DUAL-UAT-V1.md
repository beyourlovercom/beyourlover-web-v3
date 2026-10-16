# BYL Native-First Development & Dual UAT V1

**Canonical BYL Website V3 operating standard** · Owner approved 2026-10-16 · Mandatory under root [AGENTS.md](../../AGENTS.md)

This policy governs **how** agents implement and accept interactive websites. It does not authorize deployments, merges, credential changes, real transactions or business-rule changes. GitHub remains the sole code source of truth; real WooCommerce/WordPress runtime evidence remains distinct from code evidence.

## 1. Principle and design authority

> **Design baseline approved first; native controls and functional UAT before visual detail polish; functional regression after every material visual change; close a page only after Functional PASS and Owner Visual PASS.**

A design sketch can be Owner-approved and frozen before source implementation. That is **Design Baseline PASS**, *not* proof that the rendered application works. Do not begin new page-wide redesign cycles because native functionality is failing.

The term **native** means use the existing authoritative module, not merely a control with matching HTML or CSS:

| Domain | Canonical authority | Theme / agent responsibility |
| --- | --- | --- |
| Cart items, quantities, coupons, cart totals | WooCommerce Cart / Store API and existing approved integrations | Render and interact through actual Woo contracts; preserve update/error semantics |
| Address fields, country/state, shipping/tax calculations, checkout validation | WooCommerce checkout engine and native scripts | Style and position existing fields; preserve SelectWoo, events and native validation |
| Shipping discounts, bundle/BOGO policy | Andy Commerce | Display approved policy results; never hardcode business thresholds in theme |
| Payment eligibility, provider widgets, nonce, 3DS, order commit | WooCommerce plus enabled payment gateway providers | Render eligible native methods without recreating payment forms or bypassing gates |
| Generic WordPress foundations and shared capabilities | Andy Core / existing WP contracts | Call through published contracts; avoid an unofficial duplicate implementation |
| Theme design, responsive layouts, feedback and progressive presentation | BYL V3 Theme | Make authorized visual changes without overwriting any authority above |
| Fulfillment, inventory, accounting and ERP payment status | BYL ERP under its approved integration contract | Do not independently mutate ERP business truth |

Before adding an input, dropdown, button, link or accordion, **identify its real owner, field/DOM/API, event lifecycle, persistence, authorization and default fallback**. Verify whether the site uses Woo Classic checkout, Block checkout, Store API, or provider-specific extensions. A custom Drawer can contain *native Woo fields* and nevertheless break them through overlays, focus traps, JS reparenting, hidden-required state, or provider reinitialization.

**Prohibited shortcuts:** fake card methods/price/rate previews presented as live; cloned inputs that stop real Woo validation; synthetic Woo orders for visual tests; shadow business rules; hiding required Woo errors or legal consent; marking a mock screenshot as live Owner PASS.

## 2. Required execution order — stage gates

| Gate | Output | Exit condition |
| --- | --- | --- |
| D0 · Freeze approved design | Owner baseline (screen/desktop/mobile, field ordering, CTA, step sequence), scope and current authoritative UI | **Design Baseline PASS**; material revisions require a fresh Owner design decision |
| F0 · Control inventory and ownership | Per-control checklist: exact native owner, selector/API, native event, state and error/fallback | Every **in-scope** control listed with an owner; unknowns explicitly BLOCKED |
| F1 · Isolated functional implementation and tests | Native integration, click/keyboard/touch/field-validation/unit/DOM tests including negative states | All affected P0/P1 cases pass in isolation; no regression from custom CSS/JS |
| F2 · Authorized real Dev functional UAT | Genuine browser on permitted Dev environment, populated cart/session where needed, logs/screenshots of native state changes | **Functional LIVE DEV PASS** for every in-scope critical control; no unsafe write without explicit test authorization |
| V1 · Visual detail polish | Compare real rendered Dev to frozen Owner baseline; typography, color, spacing, safe-area, responsiveness | Visual changes reviewed against **real runtime**, not fixture alone |
| R1 · Post-polish functional regression | Re-run affected native controls and negative cases after visual/CSS/JS changes | All F1/F2 controls affected by polish remain functional; any P0/P1 failure returns to F1 |
| U1 · Dual Owner UAT | Functional and Visual Owner acceptance records, exact SHA, environment, unresolved items | **Functional PASS AND Owner Visual PASS**, with independent approved integration gates where required |

This is **page-by-page**: complete its function→visual→regression loop, then proceed to the next page. Integrating a second page never waives a first page's failure. Cross-page end-to-end regression follows the page gates, not instead of them. **Neither a mock, lint, HTTP 200, green CI, deployed ZIP nor an attractive screenshot is a substitute for live functional evidence.**

An Owner-approved redesign or completely new interaction must obtain *visual proposal PASS before implementation*; Native-First does not override that existing Owner authority. High-risk pages may develop CSS behind an isolated fixture in parallel, but **no visual-only completion claim or production rollout** while native P0/P1 controls fail.

## 3. Per-control acceptance contract

For **every interactive control in scope**, record:

1. **ID / page / visible label and exact DOM selector** (one native field, one authoritative form).
2. **Owner and source** (Woo field / Store API / WP core / Andy Commerce / Andy Core / Theme; real plugin and method).
3. **Valid input and click/focus/keyboard/touch behavior** (including SelectWoo search and overlay pointer hit-test where applicable).
4. **Expected native event and dependent state** (country→province, address→shipping rates/tax, coupon→discount/total, quantity→cart totals, gateway→eligible method/disabled submit).
5. **Error, absence and fallback** (required validation, unavailable zone/provider, slow network, rollback, ordinary Checkout path).
6. **Viewport/context** (at least ~320px/~390px/real iPhone 14 for difficult mobile flows and desktop ~1440px, guest/signed-in if relevant).
7. **Proof** (exact GitHub SHA and Dev deployed SHA, browser/network/console evidence with personal details redacted, test run, Owner decision).
8. **State** (never infer PASS from a different level of evidence).

| State | Meaning |
| --- | --- |
| UNTESTED | Defined but no reliable test |
| SOURCE-ONLY | Source mapping/static check only |
| ISOLATED PASS | Native-vendor/local DOM/mocked tests pass; not real Dev |
| LIVE DEV PASS | Genuine Dev service and browser prove this control in an authorized context |
| FAIL | Known missing/incorrect native behavior; blocks relevant page closure |
| BLOCKED | External dependency/permission/capability prevents proof; NOT a pass |

**P0:** unusable input, lost cart/address/amount, wrong shipping/tax, hidden validation, gateway false-success, broken native fallback. **P1:** impaired legitimate interaction, inaccessible required choice, significant state loss or misleading noncritical message. P0/P1 functional defects block subsequent visual fine-tuning and page Owner Final. Lower-risk cosmetic P2/P3 can follow F2 with documented scope and regression.

Do not store checkout personal identifiers, card data or secrets in a public repository or screenshot evidence. Retain a redacted evidence pointer when appropriate.

## 4. Commerce sequence and ownership

The Owner-frozen customer journey for Website V3 is **Cart → Shipping → Payment** across the approved Cart Drawer and Checkout Drawer, with fallback routes **/cart/** and **/checkout/** when necessary. The current Cart→Checkout handoff may still perform one secure page navigation; a strictly zero-navigation transition is a separate, unpassed gate. **Delivery methods live within Shipping; review/details live within Payment; do not invent a fourth Delivery/Review customer step.** The native payment processor still controls the *final payment action* and validation.

Current [Checkout Native Functional UAT V1 — Issue #125](https://github.com/beyourlovercom/beyourlover-web-v3/issues/125) gives **54 tracked checks**:

| Area | Count | First acceptance gate |
| --- | ---: | --- |
| Cart Drawer | 10 | Quantity +/−, remove, coupon, true shipping/promo, route, errors |
| Shipping | 22 | Email, native Country/Province, fields, validation, real rates, update and retention |
| Payment | 12 | Real eligible gateways, native wallet provider, total, legal/error and state controls |
| Cross-flow | 10 | Guest/account, keyboard/touch, blank/slow/error, fallback, end-to-end and device matrix |

**Issue #125 tracks coverage, not completion.** Do not mark all 54 PASS until each is genuinely proven. Where a payment or order test would create a real business event, keep it **BLOCKED** until a separately scoped/authorized sandbox/Canary is available; the payment gateway's **parallel Track B** owns provider readiness, not the Visual Track A. A designer cannot grant payment authority with a visual PASS.

A specific incident illustrates this rule: WooCommerce's native SelectWoo dropdown is appended to document.body with a lower z-index than the custom Drawer overlay; a visually correct Country field was not clickable. **Native fields existing in the DOM was not evidence of working interaction.** Continue to require real Country/Province selection and downstream rate refresh, not merely a screenshot or initial SelectWoo popup.

## 5. Risk-scaled reuse across BYL site

Apply the same ownership and dual-gate **principle** to PDP, Pages, Posts, policies, marketing landings, account/KOL and admin modules while scaling the checklist to actual risks:

- **Static text/Blog/policy**: links, table of contents, language/navigation, media and mobile reading experience; no artificial payment test requirement.
- **Interactive but non-transactional**: search, filters, forms, login, consent, buttons, keyboard, server validation and error handling.
- **Commerce/identity/admin**: full native state, permission, shipping/pricing/gateway role contracts, fail-closed negative tests, and live Dev evidence.
- **Critical payment/orders/DB/WWW**: require separate explicit Owner authorization, isolation, exact-head deployment controls, backup/rollback and operator review.

Retest only controls affected by a small isolated fix plus smoke tests of upstream/downstream dependencies. Reuse previously valid evidence **only while its revision, dependencies and runtime state remain compatible**. Do not constantly repeat all 54 checks for unrelated isolated copy tweaks, but do not omit them at Checkout Final.

## 6. Parallel work, evidence and release boundaries

- GitHub main remains **the only final code merge destination**. Use one isolated worktree/feature branch per task, avoid concurrent edits to the same file, reconcile stacked PR dependencies and obtain separate merge approval.
- **Functional tests can be written during implementation**. Production mutation is not implied: mock the Store API, native-vendor scripts and failure cases locally. For real Dev, read-only inspection does not authorize Woo cart/session/coupon writes; obtain a specifically approved isolated test session or Owner-supervised action when those are needed.
- For each Owner UAT: record Design Baseline / Functional / Visual as **three distinct states**, with exact source+deployed commit, mobile/desktop evidence, excluded dependencies, and rollback.
- Before Dev runtime change: applicable Owner authorization, exact-head and allowlisted files, expected existing SHA, off-webroot backup/rollback and real HTTP/browser proof. Before **WWW, production payment, customer/order/database writes, email, account or gateway credential changes**: separate explicit approval, irrespective of this policy.
- Hosted CI cannot be assumed PASS when GitHub billing prevents job startup; locally passed tests may only substitute under the Owner's explicit CI exception for the specific rollout.
- A Control/Owner UAT FAIL is never converted to PASS by a new screenshot, visual CSS patch or a successful bundle build.

## 7. Canonical references and checklist upkeep

- Root [AGENTS.md](../../AGENTS.md) — mandatory top-level rules.
- [TESTING.md](TESTING.md) — risk-based evidence and Owner gates.
- [ARCHITECTURE.md](ARCHITECTURE.md) — source ownership and scope.
- [COMMERCE.md](COMMERCE.md) — three-step Cart/Shipping/Payment customer flow.
- [RELEASE.md](RELEASE.md) — Dev/WWW cutover gates.
- [GitHub #125 Native Functional UAT inventory](https://github.com/beyourlovercom/beyourlover-web-v3/issues/125) — 54 control-level records. The Issue is a tracking checklist; its current status must not be represented as PASS merely because this standard was published.

When the Owner changes this policy, update root AGENTS, this canonical standard, related domain/testing docs, the governance contract test and Issue/checklist within one controlled PR. **Documentation alone does not change runtime behavior**.
