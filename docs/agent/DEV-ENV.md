# Dev-first environment contract — BYL Website V3

## Environment responsibilities
- **GitHub**: canonical source, PRs, exact HEAD, review/rollback history.
- **`https://dev.beyourlover.com`**: authoritative *new-site* development, plugin/content configuration, theme integration and Owner UAT.
- **`https://localdev.beyourlover.com`**: optional isolation for risky work, reproducing bugs, package or environment experiments. Not a blanket sign-off gate.
- **`https://www.beyourlover.com`**: existing customer-facing legacy store. No change without distinct Production/Cutover authorization.

## Delivery loop for approved work
1. Inspect relevant actual Dev behavior and repository state; verify target branch/current SHA.
2. Implement in isolated Git branch/worktree; make scoped commits and relevant tests.
3. Back up affected **Dev** files/settings before code-only integration; deploy from exact Git commit/package with hashes when feasible.
4. Verify live Dev behavior, including real checkout/browser paths and mobile width when relevant. Fix within approved scope without repeated Owner permission.
5. Record PR, exact SHA, test evidence and rollback; stop at required visual/Owner UAT gates.
6. After Owner acceptance and required checks, merge according to branch protection/approved review flow; do not infer WWW deployment authorization from a merge.

## Boundaries
- A Dev checkout test is not an excuse to create fabricated Woo orders or simulate payment success.
- Don't blindly copy zone IDs, payment options, secrets, gateway credentials, or production customer records across sites; verify equivalence and provenance.
- Environment-dependent behavior (SSL, CDN, R2, email, cache, webhook, gateway, cron, permissions) must be tested on Dev as applicable.
- Protect unrelated local worktrees, user changes and previously accepted UAT behavior. No forced resets, machine restarts or parallel same-file agents unless narrowly justified and safe.
- Do not mistake the Dev Hostinger SSH directory for the WWW origin. The final WWW source/target, database, DNS and rollback must be verified separately.
