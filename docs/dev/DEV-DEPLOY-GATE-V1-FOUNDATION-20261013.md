# BYL Dev Deploy Gate V1 — Foundation handoff / 2026-10-13

## Scope, goal and operating boundaries

**Scope:** a standardized, reusable *offline candidate verification pipeline* for a future approved Dev-only deployment. Primary fact source: GitHub `beyourlovercom/beyourlover-web-v3`. **DO NOT MERGE OR DEPLOY** without a separate Owner authorization and a security-approved write transport. This branch is independent of the already-open Cart #80 feature branch.

**The actual remote platform security checker previously blocked a Windows package/deployment operation.** A working Windows→Hostinger SSH **read-only** connection and all devices reporting READY are not evidence of remote write permission. Never switch transports, change OAuth, split/obfuscate commands or disable inspection merely to bypass a block.

## Implemented / tested

- `tools/dev/deploy_gate.py` builds deterministic *local* three-file ZIP + JSON receipt. There is intentionally **no remote upload, apply, SSH, WordPress command, DB operation, payment, order, restart or deploy action**.
- Precisely pinned repo checkout SHA (must equal clean Git HEAD) and three allowlisted Theme paths:
  - `theme/beyourlover-v3/inc/cart-drawer.php`
  - `theme/beyourlover-v3/assets/css/cart-drawer.css`
  - `theme/beyourlover-v3/assets/js/cart-drawer.js`
- Exact previously verified **Dev** old SHA:
  - PHP: `a3af11253263d16e9da7de3be78e93fdf03bc3e197f2a38923844f584aded443`
  - CSS: `f561b3948ca711079c304d54448a0aa207cf51e2dec038f6aecab418828661bb`
  - JS: `267c3fe43c745e5171bfdf644ad9cd36d962e30938b8e9290d7ca6248a50a47c`
- Rejects modified checkout HEAD, extra/missing or symlinked files, oversize/binary, wrong target (`dev.beyourlover.com` only), malformed receipt, unsafe ZIP member, baseline or candidate digest mismatch and fake remote-authorization claims.
- `tools/dev/test_deploy_gate.py`: **13/13 offline unittest PASS on Windows 007**, SHA `925f361baba59517ad6f02c655ea395bb4f688ee` at test time. All tests use temporary files, no remote or user business writes.
- `.github/workflows/dev-deploy-gate-v1-ci.yml`: PR and development-branch CI, read-only repo access, no deployment secrets, two deterministic independently built candidates byte-compared, ZIP+receipt verification, short-lived evidence artifact. It **cannot deploy**.
- Separate Dev Gate CI should run for this Draft PR before any main merge. No current exact-head CI status should be assumed prior to an actual successful workflow run.

## Correct usage (after this code becomes an approved common tool)

```bash
# Checkout the exact approved Cart feature commit, with this reviewed gate
# script already present in that approved checkout (do not copy around a
# previous security inspection block).
SHA=$(git rev-parse HEAD)
python tools/dev/deploy_gate.py build --exact-sha "$SHA" --out ./candidate-evidence
python tools/dev/deploy_gate.py verify \
  --zip ./candidate-evidence/cart-m7.2-dev-only-candidate.zip \
  --receipt ./candidate-evidence/cart-m7.2-dev-only-candidate.receipt.json
```

**Important:** Candidate ZIPs built from the independent **foundation** PR branch contain that branch's current Cart sources, **not** the unmerged Cart #80 fix. They are only format/provenance evidence and **must not be deployed as the Cart correction**. Before applying the Cart correction, reconcile the approved gate with **exact** Cart #80 code in an approved checkout; do not merge #80 merely to reuse this tool.

## Protected manual remote-apply candidate (STAGED, DEFAULT OFF)

Now included in the same Draft PR; **NOT active or deployed**:

- `.github/workflows/dev-only-cart-manual-apply.yml`: `workflow_dispatch` **only**, never `push`/`pull_request` execution. `plan` performs read-only Gate CI and builds an exact Cart PR #80 candidate. `apply` is blocked unless the repository variable `BYL_DEV_DEPLOY_ENABLED=true`, an explicit Owner confirmation phrase, exact current Cart #80 SHA, a completed successful Foundation CI for that SHA, and protected GitHub Environment `byl-dev-protected` approval are all present.
- Environment needs **Owner configured** restricted Dev-only secrets `BYL_DEV_SSH_HOST`, `BYL_DEV_SSH_USER`, `BYL_DEV_SSH_PRIVATE_KEY`, `BYL_DEV_SSH_KNOWN_HOSTS`; no credentials were obtained, modified, copied from Windows, or installed here. Require approved environment reviewers before enabling the repository variable.
- The remote `tools/dev/apply_dev_cart_m72.sh` script targets a hardcoded Dev path, independently checks staging + exact old file SHA + bundle member allowlist + SHA, backs up off-webroot, atomically installs only three files, and runs PHP lint / three read-only GET checks. Failure triggers rollback attempt. Script is authored, **not executed** on Dev. Static protection contracts and Bash syntax are part of CI.
- This is a separately reviewed, protected *future* write transport, not a way to retry or bypass the prior platform security denial. It will only become callable after a separately authorized Foundation PR merge, environment setup, and explicit Dev deploy approval.
- After actual approved Dev deploy, M390/M1440 *real files, non-mocked DOM* are required before Owner Final UAT; CI mock screenshots cannot replace that gate.

## Remote apply safety prerequisites and required human gates (NOT activated)

1. Verify exact candidate PR SHA and the related **completed exact-head** CI, Owner's Dev-only scope and the specific source diff.
2. Establish a demonstrably security-approved write channel (official remote MCP execution if allowed; otherwise separately approved protected GitHub Environment and credentials). This tool does not grant or create that permission.
3. Protected environment **Dev only**, environment reviewers, short-lived scoped credentials, host key verification, no WWW/Production secret access. Never copy existing US Mac or Hostinger credentials into GitHub without explicit Owner approval.
4. Before write, verify **on the remote server** home `https://dev.beyourlover.com`, `WP_ENVIRONMENT_TYPE=staging`, actual exact old SHA for each file, and artifact SHA. In case of drift, fail closed; don't force replace.
5. Create unique off-webroot rollback, deploy exactly the allowlisted three files atomically only through approved transport, check hashes and PHP syntax, then Chrome M390 and PC1440 postdeploy: steps/Terms/icons must **scroll** and only CTA must stay fixed. No Woo add-item, checkout submission or payment.
6. Record evidence, backup and rollback pointer; only then request Owner screenshot UAT. No automatic main merge or WWW rollout.

## Status

**Foundation Gate implementation / tests: PASS. CI after PR opened: PENDING independently confirmed. Dev Cart M7.2 release: HOLD on remote write permission and exact source CI. GitHub merges, WWW and real transactions: not authorized.**
