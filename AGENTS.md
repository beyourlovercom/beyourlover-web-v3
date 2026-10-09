# BYL Website V3 — Agent Governance V2
> Applies to this repository and the new BeYourLover website only. The Owner-approved operating model is Dev-first. Do not inherit a generic Local-first mandate from shared-plugin repositories.

## Mission and source of truth
- Build **the complete new website** on `https://dev.beyourlover.com`; tune theme, plugins, content, integrations and business flows together until full Owner UAT passes.
- **GitHub is the only code source of truth.** Work on a scoped branch/worktree; no untracked or direct Dev-only source fixes. Record exact commit SHA for deployed code.
- **First-party plugin limit:** Andy Core (generic) + Andy Commerce (B2C) only. BYL Platform is legacy/frozen for rollback; no new independent business plugin or feature work inside it without explicit Owner architecture approval. BYL V3 Theme is presentation, not a plugin.
- `localdev.beyourlover.com` is an optional isolation/risk sandbox, not a compulsory gate for every BYL change.
- Current `www.beyourlover.com` is the live legacy store. **Never** deploy, mutate, retire a plugin, change routing/DNS or migrate data there without a separate explicit Owner authorization.
- Launch is a **full Dev-to-WWW cutover project**, not incremental feature deployment onto old WWW. Cutover needs its own plan, data delta synchronization, payment/SEO/ERP checks and tested rollback.

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
- [Dev environment and integration](docs/agent/DEV-ENV.md) — local/Dev work, deployments, branch handoff
- [Cart, Checkout, payment contracts](docs/agent/COMMERCE.md) — commerce/customer-funnel work
- [Testing and Owner UAT](docs/agent/TESTING.md) — test selection, browser evidence, closure criteria
- [Security and data boundaries](docs/agent/SECURITY.md) — auth, PII, admin, payments, secrets
- [Release and Dev-to-WWW cutover](docs/agent/RELEASE.md) — staging/release/cutover planning

Read only the needed files and related source/test code. Existing feature docs and accepted UAT contracts override guesses; on conflict, flag it rather than silently changing a frozen decision.
