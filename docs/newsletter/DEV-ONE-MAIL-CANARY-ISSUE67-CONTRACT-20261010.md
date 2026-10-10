# BeYourLover Newsletter M3.7 — Dev one-message mail gate (Issue #67)

**Scope:** code-only security review; **NOT approved for Dev installation, mail dispatch, public signup, GitHub main merge, or WWW/Production use**. GitHub main is the sole authority. This design reuses the existing Dev Safety MU-plugin and creates **no additional plugin**.

## Problem and existing boundary

The existing `dev/mu-plugins/byl-dev-safety.php` globally denies WordPress mail with `pre_wp_mail => false`, including G5 payment/test-order notifications. Newsletter M1's subscription POST requires Core API **and** outbound flags; it cannot truthfully send a real confirmation message while the Dev mail guard denies all attempts. A prior temporary in-process exception was blocked by an execution safety gate, never executed, and cleaned up. This branch implements an auditable shared safety hook instead of bypassing the live guard.

## Proposed default-OFF one-use contract

The **same existing MU-plugin** retains a `pre_wp_mail` callback that returns `false` unless every guard succeeds; it respects earlier non-null vetoes. It allows only one native `wp_mail` attempt, from the **Dev staging WordPress CLI** on `https://dev.beyourlover.com`, after a trusted operator has separately provisioned the short-lived ticket below. There is no HTTP endpoint, user-facing admin action, or automatic ticket creator.

Permit file (created **only through a separately approved secure operator workflow**, outside WordPress webroot):

`/home/u247343654/byl-v3-staging/newsletter-one-mail-permit.json` (permissions **0600**).

Illustrative keys (**NOT a real valid ticket**; do not put mailbox plaintext, secrets, or confirmation links into GitHub):

```json
{
  "purpose": "newsletter-confirmation-canary",
  "site": "https://dev.beyourlover.com",
  "state": "approved",
  "approved_by_owner": true,
  "max_messages": 1,
  "ticket_id": "<32 lowercase random hexadecimal characters>",
  "recipient_sha256": "<sha256 of the normalized approved test email>",
  "backup_path": "<exact approved Dev full database backup path outside webroot>",
  "backup_sha256": "<sha256 of that already verified backup>",
  "issued_at": 0,
  "expires_at": 0
}
```

An operator must supply genuine short-lived epoch timestamps (at most 300 seconds) and the verified database backup fingerprint. Invalid, missing, expired, replayed, non-0600, or improperly located tickets **fail closed**. The ticket is consumed **atomically before** any transport attempt using a separate exclusive lock and secure replacement. If SMTP/PHP mail errors, times out, or has uncertain delivery, **no retry on the same grant** is permitted. No out-of-band adapter may treat transport acceptance as mailbox delivery.

### Payload and transport checks

Only `Confirm your newsletter subscription` can pass. Exactly one intended To address must match the SHA-256 digest, with **no Cc/Bcc/Reply-To, custom headers, attachments or embeds**. Body must exactly match the Andy Core confirmation text and contain distinct 64-character confirm and unsubscribe tokens on the **Dev origin only**. Mailer-stage verification rejects recipients or subject altered after the `wp_mail` filter. Normal payment, Woo order, automation and ERP mail remain denied; no generic mail unblock.

The ticket holder must not print the recipient email, raw message, tokens, credentials, or SMTP response secrets in GitHub / ordinary logs. Final transport and Gmail receipt checks should report status/counts only.

### Remaining owner-controlled runtime operations (not granted by this PR)

1. Shared Dev Safety/G5 owner code review; independent exact-head CI; verify unchanged default denial in checkout and payment regression.
2. Obtain an **explicit separate approval** to deploy this exact code **to Dev only**. Verify the backup, staging/Production isolation, original MU-plugin SHA and rollback path. Do not replace a newer G5 version or weaken it.
3. Independently approve an audited, private, single-use ticket and a supported operator execution path. No public Newsletter API activation; scope any Core flags to the approved test process and leave runtime default OFF outside it.
4. Send **at most one** genuine test confirmation email to the Owner-designated mailbox; record the native transport outcome, then independently confirm Gmail receipt. There must be no retry after uncertain delivery.
5. The Owner must deliberately POST to confirm the token. Check the expected `pending → subscribed` state, single-use/replay protection, and then clean up **only the authorized synthetic record**. Verify API/outbound OFF, permit consumed/revoked, default site options unchanged, public Newsletter page safe, no G5/ERP/WWW side effects.
6. If the Mail Safety owner rejects the design, CI fails, or the execution safety gate blocks use, **stop** and leave real mail Canary unverified. Synthetic WordPress/MySQL/transport interception CI is not a substitute for delivery.

## Code-only acceptance evidence

- PHP syntax for the existing MU-plugin and new offline harness
- `tools/dev/tests/newsletter-one-mail-safety-smoke.php`: **30** positive/negative scenarios and cross-process concurrent grant race
- Valid single attempt plus replay rejection; missing/expired/future/revoked/overlong ticket, nonexistent/tampered/mismatched backup, wrong recipient, extra To/Cc/Bcc, altered subject/body/token, invalid site/environment/CLI, prior mail veto, wrong workflow, improper message count
- Mailer-stage To/Cc/Bcc/subject mutation negative cases
- Existing P1.3 G2 / P5 / P7 Dev Safety regressions and Foundation CI must remain PASS

**Open points for reviewers:** the permit has a trusted manual provisioning dependency; receipt/delivery requires a separate provider check; using a process-local CLI test does **not** validate public signup activation. Do not deploy or promote this mechanism while those approvals are pending.

**Roadmap:** M3.0–M3.7 synthetic PASS → Issue #67 code-only review / Draft PR → security Owner gate → Dev-only release UAT → one-mail Canary → Core/ERP true E2E separately → Owner final closure.
