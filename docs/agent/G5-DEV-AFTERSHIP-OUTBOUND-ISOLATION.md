# G5 — Dev-only AfterShip / Automizely WordPress outbound isolation

## Approved scope
Owner approved `approve G5 Dev-only outbound isolation`. This is **not** approval for new Test Mode orders, 3DS challenges, main merge, database writes or WWW deployment. Do not install another WordPress plugin, change AfterShip/Automizely provider settings or touch ERP.

## Design / exact isolation boundary
Use the **existing** `dev/mu-plugins/byl-dev-safety.php` with a highest-priority `pre_http_request` filter.

- Permit an interception **only if** `wp_get_environment_type() === 'staging'` **and** `home_url() === 'https://dev.beyourlover.com'`. This must also work for WP-CLI, cron and background jobs without an incoming HTTPS request.
- Preempt outbound WordPress HTTP API requests to `automizely.com` / `aftership.com` and *their DNS subdomains* only. Parse URL hostname and require an exact DNS-label boundary. Never match by substring and never add a wildcard for WooPayments, Stripe or all web requests.
- Return `WP_Error('byl_dev_aftership_outbound_closed')` before any network call; preserve already-preempted HTTP requests and unrelated hosts. The original G2 checkout gate, P5 Store API block, P7 synthetic Woo native `order.*` webhook suppression and `pre_wp_mail` remain unchanged.
- No logging of credentials, cookies, Provider requests or customer data. WP REST or authenticated user interfaces are not changed.
- **Coverage limitation:** protects outbound requests using WordPress HTTP API from the Dev server. It cannot stop external Automizely jobs already held by the Provider, existing queued remote messages, inbound Provider polling, JavaScript `fetch` sent from the visitor browser, non-WP-HTTP `curl_exec` or other direct TCP sockets. It is not proof that a new synthetic Test Mode order cannot trigger customer notification or ERP processing. Do **not** close the G5 real-transaction gate on this filter alone.

## Before Dev-only deployment
1. Revalidate **GitHub exact-head + successful Foundation CI** and confirm unchanged existing Dev MU hash `cce2a66b0f784b167b6500530b0f9b5d9326f0a4924b1aa88c9728e717d86e6b` or reconcile version drift.
2. Confirm `siteurl` and `home` point to **Dev**, environment `staging`, WooPayments **Test Mode**, off-webroot G2 grant ABSENT/CLOSED. No active provider/payment test window.
3. Back up current MU off-webroot mode 0600 under a new private mode 0700 directory. Verify exact source Git Blob SHA and SHA-256; stage a temporary file and atomically replace **only** the existing Dev MU; rollback on hash/post-deploy check failure.
4. Execute `php -l` and isolated no-network tests for this filter, G2, P5, P7. Verify `pre_http_request` filtered response in WP-CLI using `apply_filters` only, **not** `wp_remote_post`/network traffic.
5. Verify `G2_CLOSED` and anonymous Checkout / Store API 403 after deployment; do not call Place Order.
6. Do not merge the PR or deploy WWW without a separate scoped authorization.

## Offline contract
`php tools/dev/tests/g5-aftership-dev-outbound-isolation-smoke.php` exercises:
- Real provider root/subdomain, mixed-case, port, userinfo and trailing-dot hostnames: **blocked**.
- Lookalike attack domains (e.g. `api.aftership.com.attacker.example`) and unrelated WooPayments / Stripe / WP HTTP hosts: **preserved**.
- Production/noncanonical hosts, earlier HTTP interceptions: **preserved**.
- Existing G2, P5, P7 and Dev mail hooks stay registered.

## Owner / UAT boundary
The UI for G5 declined cards, 3DS challenge and retry requires additional *separate* live Test Mode permission, a bounded order budget and external/provider-side notification assessment. No live test is authorized by this infrastructure PR. The existing synthetic lifetime cap was last read as **1 / 6**, but must be rechecked before every live test.

## Rollback
Restore exact off-webroot MU backup to the Dev MU path, validate checksum, then rerun closed-gate/read-only smoke. Do not overwrite a newer Dev MU file if concurrent changes are observed. Preserve the backup and evidence.

## Related
- G5 Checkout submission lock: Draft PR #44 (separate UI/transaction state work).
- G3 Owner UAT: merged PR #40.
- G2/P7 existing native Woo guards: `dev/mu-plugins/byl-dev-safety.php`.
