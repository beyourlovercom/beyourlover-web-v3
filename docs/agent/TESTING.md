# Risk-based testing and Owner UAT

## Mandatory Native-First Development & Dual UAT V1

Root [AGENTS.md](../../AGENTS.md) makes [Native-First Development & Dual UAT V1](NATIVE-FIRST-DUAL-UAT-V1.md) the **top-level completion contract** for interactive BYL Website work. Risk-based test *selection* continues to apply; risk-based testing is not permission to omit native function acceptance.

- Start from an Owner-approved, **frozen visual baseline** (for materially new UI obtain proposal PASS first), then map every in-scope interactive control to the existing native owner, field/API, event lifecycle, state, validation and fallback.
- **Functional first:** inventory controls; fix P0/P1 native failures; prove actual click/touch/keyboard/input, state changes and errors before visual-detail polish. A CSS-only patch can break a Woo SelectWoo dropdown or gateway iframe and therefore needs affected interaction regression.
- **Visual second:** calibrate the real working Dev page to the approved design. After each materially interactive CSS/JS/layout change, repeat affected native control checks.
- **Dual UAT for closure:** Functional **LIVE DEV PASS** and **Owner Visual PASS** are separate required records, alongside Design Baseline PASS; all three must be grounded in the same compatible source/deployed code. A mocked/browser fixture PASS is only **ISOLATED PASS**, not live evidence.
- Track **UNTESTED / SOURCE-ONLY / ISOLATED PASS / LIVE DEV PASS / FAIL / BLOCKED** per case. An unavailable Woo gateway or unauthorized payment attempt stays BLOCKED and does not become PASS through an attractive mock.
- At Checkout, use the [54-control Issue #125](https://github.com/beyourlovercom/beyourlover-web-v3/issues/125) (Cart 10 / Shipping 22 / Payment 12 / cross-flow 10); complete page-level functional/visual/regression loops before overall cross-flow E2E. Read/write and payment authorization rules remain unchanged.
- Scale control inventories to the nature of Pages, Blog, PDP and policy content; never impose checkout payment cases on non-transactional pages.

## Do not prescribe one command for every task
Select tests by impacted behavior, then ensure meaningful coverage. Faster models do not justify skipping regression or fabricating evidence.

| Risk | Typical change | Minimum validation |
| --- | --- | --- |
| Low | Docs, isolated CSS, copy | Diff/format checks; actual affected browser view for UI |
| Medium | Cart/Checkout rendering, hooks, forms | Syntax/static checks, focused business tests, desktop/mobile browsers, console and regression against frozen states |
| High | Shipping, discounts, roles, API contracts | Above + negative/edge cases, real Dev service integrations, fail-closed behavior and rollback |
| Critical | Payment, order mutation, migration, production cutover | Separate authorized plan, gateway/canonical evidence, read-only preflight, Canary, monitoring, rollback and Owner gate |

## Browser checks
- Mobile first; inspect roughly 390px width and desktop 1440px/1536px. Use additional widths when a breakpoint or difficult UI warrants.
- Test actual URL, state transitions, form errors, navigation, totals and sticky actions, not merely HTTP 200 or class existence.
- Checkout: empty cart, real cart session, address editing, country/zone selection, discount, unavailable shipping, absence of gateways, failed payment and retry when relevant.
- Never create synthetic real orders simply to turn a test green; no externally charged transaction or email send without its own gate.
- If Playwright/browser is inaccessible, state the coverage gap rather than claiming visual UAT.

## Evidence and closure
- Test logs should show the actual command, exact revision, result and any excluded cases.
- CI missing/queued is not CI PASS; Dev smoke is not Owner PASS.
- For new or materially changed UI, first receive Owner visual proposal PASS. After implementation, open the actual affected Dev pages for Owner inspection; Owner's explicit PASS is required to mark UI work closed.
- In-scope defects may be debugged and retried autonomously. Escalate only material scope changes, unsafe operations or Owner gates.
- Preserve rollback copies for deployed Dev code/config; clean temporary credentials, logs and screenshots as appropriate.
