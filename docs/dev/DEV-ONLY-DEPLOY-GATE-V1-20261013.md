# BYL Dev-only Deployment Gate V1 — candidate, no deployment

**Date:** 2026-10-13. This is a documented, reusable process, **not authorization to disable tool security or deploy to WWW**.

## Source and authority

- GitHub `beyourlovercom/beyourlover-web-v3` is sole source of deployable theme files. Use an exact **commit SHA**, not a moving branch name.
- Every candidate needs passing tests/CI for that exact commit; an absent workflow run means **not verified**, not PASS.
- GitHub main merges, WWW/Production deployments, Woo DB migrations, payment/order changes, READY device restarts and OAuth/security-permission changes are **different authorization gates**; this process authorizes none of them.
- Reuse repository **Foundation CI**, **Theme OS reproducible ZIP verification** and existing, explicitly authorized Windows→Hostinger SSH capability. Do **not** install a new plugin or create a parallel deploy source.
- If an execution is stopped by platform/remote security inspection, stop that operation; do not fragment, obfuscate, switch command transports or disable safety controls to bypass it. Resume only once a genuinely authorized deployment capability/path is available.

## Gate 0: read-only provenance / environment

1. Read current exact GitHub PR HEAD, main, required tests and completed CI.
2. Read SSH Hostinger target: `wp option get home` must equal `https://dev.beyourlover.com`; `WP_ENVIRONMENT_TYPE` must be `staging`; verify expected theme directory exists.
3. Compare each live file's SHA256 against the reviewed baseline. Mismatch → HOLD and audit concurrent changes; never overwrite.
4. Limit candidate to an exact file allowlist; inspect diff and confirm no secret / Woo transaction / PHP gateway changes.
5. Record backup and rollback destination **outside webroot**, unique per deployment ID.

## Gate 1: offline package; no Hostinger writes

1. Fetch or checkout exact GitHub SHA into a **disposable** workspace.
2. PHP lint and JS syntax plus feature regression/DOM contract, specifically M390/PC1440 checks for Cart.
3. Reuse existing Theme OS reproducible build/verification when whole-theme ZIP is genuinely needed; for bounded patches create a 3-file archive and SHA256 manifest. Archive must contain **only** allowlisted relative paths, reject symlinks/traversal and extra files.
4. A failed or missing exact-head CI check is HOLD, not "CI exception" by default.

## Gate 2: Dev-only deploy; requires an approved writing transport

1. Use only an established remote command/file-deployment operation **if actually permitted by security inspection**. Do not use an alternate tool to evade a denial.
2. Verify exact uploaded archive and individual file hashes, destination, environment, expected old file hashes, and a rollback backup of all modified files.
3. Atomic replacement of only allowlisted files; retain backups until Owner UAT. Any check failure must restore the previous verified files.
4. Post-deploy: PHP syntax, exact new hashes, read-only Dev home/Cart preview/Checkout GET, real M390 and PC1440 screenshot/scroll, no Woo cart POST, order placement or payment.
5. Record GitHub PR link, exact HEAD, SHA manifest, test proof, deploy receipt and rollback path in the project's roadmap. Stop for Owner UAT only after the deployed files are confirmed.

## Current Cart M7.2 candidate (as of read-only preflight)

- PR: https://github.com/beyourlovercom/beyourlover-web-v3/pull/80 (Draft, not merged).
- Proposed files only:
  - `theme/beyourlover-v3/inc/cart-drawer.php`
  - `theme/beyourlover-v3/assets/css/cart-drawer.css`
  - `theme/beyourlover-v3/assets/js/cart-drawer.js`
- Hostinger Dev read-only validation **PASS** on 2026-10-13; current predeploy SHA256 values:
  - PHP: `a3af11253263d16e9da7de3be78e93fdf03bc3e197f2a38923844f584aded443`
  - CSS: `f561b3948ca711079c304d54448a0aa207cf51e2dec038f6aecab418828661bb`
  - JS: `267c3fe43c745e5171bfdf644ad9cd36d962e30938b8e9290d7ca6248a50a47c`
- Offline candidate M390 browser and 17/17 DOM tests passed previously, but **actual Dev still has the old incorrect sticky Terms/steps behavior**.
- The exact current PR HEAD did **not** expose an associated completed Foundation CI run during the read-only check. This and the denied authorized file-preparation/write operation mean **DEPLOYMENT HOLD**.
- Safe continuation: determine the supported security-authorized writing transport and exact-head CI result. Do not ask the user to restart any READY device or relink OAuth solely because this deployment is blocked.
