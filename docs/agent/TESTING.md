# Risk-based testing and Owner UAT

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
