# A1 Hostinger cron investigation — 2026-11-06

## Corrected evidence boundary

An earlier audit reported that the user crontab contained zero lines based on `crontab -l 2>/dev/null | wc -l`. That result was not valid evidence: the Hostinger shell does not have the `crontab` or `atq` binaries installed.

Read-only inspection now confirms:
- `command -v crontab` → not installed.
- `command -v atq` → not installed.
- The user-visible `/var/spool/cron` directory is empty.
- `/etc/cron.d` and `/etc/crontab` are not exposed in this shared-hosting shell.
- Therefore the actual Hostinger hPanel Cron Jobs configuration and schedule still require hPanel access.

## Newly found Hostinger cron artifact

One Hostinger cron log/lock pair was found:
- Job/log identifier: `EYioxn1XSe`
- Log: `/home/u247343654/.logs/cronjob_EYioxn1XSe` (64 bytes; last modified 2026-11-06 08:00 UTC)
- Lock artifact: `/home/u247343654/.cagefs/tmp/cron_lock_EYioxn1XSe` (0 bytes; last modified 2026-11-03 12:00 UTC)
- The only matching Dev custom script discovered is `/home/u247343654/domains/dev.beyourlover.com/.aicc-andy-core-u3c/u3c-byl-dev-cron.sh`.
- Script SHA-256: `cc6c671446fa069e80b921174ce506c8189f9771c16be5a8c1059cdef92ba710`.

The log contains:
`2026-11-06T08:00:08Z FAIL-CLOSED: WordPress core baseline drift`

The script header explicitly says it is a draft and must not be deployed before an Owner gate. Its default mode is `--check`; mutation mode requires an explicit `--execute` argument. In execute mode it has several fail-closed gates and can only reach its native updater section if all baselines and the auto-update opt-in pass.

## Current Dev values and safety effect

Read-only WP-CLI checks on Dev returned:
- WordPress core: `7.1.4` (script expects `7.1.3`)
- YBY-Core: `2.3.1`, active (script expects installed `2.2.8` and target `2.2.9`)
- `yby_core_update_channel=canary`
- `yby_core_automatic_updates=0`
- `DISABLE_WP_CRON=true`
- No `wp_maybe_auto_update` scheduled event is currently registered.

The cron log therefore shows the script stopped at its first core-version gate before any mutation. Even if that gate were later satisfied, the current YBY-Core version differs from the expected source version and automatic updates are explicitly OFF. No plugin update or scheduled WordPress event was triggered by this investigation.

Other Dev isolation remains:
- Action Scheduler runner concurrency: `0`
- Jetpack sync sender: `false`
- `pre_wp_mail`: BLOCKED
- Action Scheduler counts unchanged: 85 pending / 14 failed / 1,541 complete

## What remains to close A1

The exact hPanel Cron Job command, arguments, cadence, and enabled/disabled status are not available from SSH. The log proves a cron execution produced the fail-closed script output, but does not expose whether the job passed `--check` or `--execute`.

No task was run, disabled, deleted, or edited. The lock/log artifacts were preserved. To close this gate, inspect the Hostinger hPanel Cron Jobs entry for ID `EYioxn1XSe` and confirm the exact command and schedule. If it is configured with `--execute`, Owner should decide whether to disable it or explicitly approve that controlled update workflow. A cloud-browser connection is required because the current local Browser integration is not available in this session.

**A1 status:** Dev WordPress ingress/egress isolation PASS; Hostinger cron configuration and provider-side job attestation remain HOLD. No claim is made that all external scheduled jobs are absent.
