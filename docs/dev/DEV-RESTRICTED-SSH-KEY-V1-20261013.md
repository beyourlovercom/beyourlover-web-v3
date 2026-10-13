# BYL Dev-only restricted SSH credentials · V1 · 2026-10-13

**Owner approval:** Proceed with a manual owner release gate and prepare **dedicated, least-privilege Dev deployment credentials**. Approval to configure the channel is **NOT approval to enable GitHub remote apply, merge this PR, deploy Cart, or alter WWW**.

## Why this extra step is necessary

The private GitHub repository's billing plan returned HTTP 422 when attempting Environment Required Reviewers. Its `byl-dev-protected` environment currently restricts deployment to `main`, but **does not provide independent reviewer enforcement**. Repo variable `BYL_DEV_DEPLOY_ENABLED=false`, environment secrets = zero. Treat Owner's per-release approval, exact SHA CI, independent release checklist and the release-time manual flag as separate gates. Never leave the flag enabled after a release.

The existing Dev workflow's generic `scp` plus `ssh bash -s` would give an otherwise dedicated SSH key excessive capabilities. That is **unacceptable** for this shared Hostinger Unix account, so V1 limits the key at `~/.ssh/authorized_keys` via OpenSSH `restrict,command=...`.

## Source of truth (Draft infrastructure PR; do not merge without owner approval)

- `tools/dev/forced_dev_cart_m72.sh`: only permits `apply-cart-m72 <full commit SHA> <ZIP sha256> <PHP sha256> <CSS sha256> <JS sha256>`, with a ZIP on standard input. Rejects arbitrary shell commands, SFTP/scp, PTY, bad SHA, ZIP over 1 MB, bad digest and wrong path. Never runs `eval`; invokes a local admin-provisioned copy of the existing approved `tools/dev/apply_dev_cart_m72.sh`. Its canonical server path is `/home/u247343654/.byl-dev-gate/forced_dev_cart_m72.sh`.
- `.github/workflows/dev-only-cart-manual-apply.yml`: replace the old two-call `scp` + remote Bash with **one** `ssh ... "apply-cart-m72 SHA..." < exact_candidate.zip`. All of the existing exact PR80/CI/Owner string/disabled repo flag/protected environment and strict Hostinger host key checks remain.
- `tools/dev/test_restricted_key_contract.py`: offline/static tests, plus real Bash rejection tests in Linux GitHub CI. **No actual Dev or WWW writes** in CI.
- `.github/workflows/dev-deploy-gate-v1-ci.yml`: new contract and Bash lint are required for the infrastructure PR.

## New P0 guard: administrator-pinned single-release approval

A dedicated SSH key alone is **not** permission to deploy arbitrary PHP. The first restricted-key candidate accepted whatever PHP/CSS/JS SHA values the caller supplied; if its private key leaked, that could install attacker-owned PHP into Dev despite having no shell or SFTP rights. The forced-command entrypoint now **fails closed** before accepting any ZIP unless this administrator-owned file exists:

`/home/u247343654/.byl-dev-gate/approved-cart-m72-v1.txt`

It must be a regular, non-symlink file, owned by the Hostinger user, mode **0600**, containing **exactly one line**:

`BYL-CART-M72-V1 <approved_40_char_commit> <approved_zip_sha256> <approved_php_sha256> <approved_css_sha256> <approved_js_sha256> <expiry_10_digit_unix_utc>`

The five SHA values must match the **independently reviewed** Cart PR #80 plan receipt and ZIP/Theme files *byte-for-byte*. The expiry must be in the future but **no more than 1,800 seconds from remote Hostinger time**. The only permitted administrator who can place/update the manifest is the existing separately safety-authorized admin SSH session, **never** the restricted SSH key, GitHub Actions workflow or an unreviewed tool. Remove this manifest promptly after completing/failing the approved release (the key also fails closed when initial Dev old-file SHA has changed). No manifest exists at present; **there is no active deployment authorization**.

Linux CI tests simulate absent receipt, mismatched SHA, expired/far-future approval and valid manifest with empty ZIP input. The actual Hostinger files are never touched by these CI tests.

**This approval is a separate release-time gate**; it is not a substitution for the GitHub plan's unavailable native Required Reviewers. Keep repository variable `BYL_DEV_DEPLOY_ENABLED=false` until Owner approves an exact release.

## Setup protocol (separate authorization and actual device inspection)

1. Verify the GitHub Actions environment is still only `main`, zero secrets and deployment flag `false`; verify destination `home` is exactly `https://dev.beyourlover.com` and `WP_ENVIRONMENT_TYPE=staging`.
2. Obtain a separately generated Ed25519 deploy key pair, private key readable only by the operator and stored **exclusively** as the GitHub Environment secret `BYL_DEV_SSH_PRIVATE_KEY`; **never** expose it in chat, command logs, repo, artifacts or stdout. Pin the remote SSH host key against the **already trusted** Windows/Owner known_hosts record (not unauthenticated ssh-keyscan alone).
3. On Hostinger through the existing **safety-authorized admin** access, install the exact reviewed two scripts outside all webroots with `0700` directory and scripts; install the public key into `~/.ssh/authorized_keys` with OpenSSH options:
   `restrict,command="/home/u247343654/.byl-dev-gate/forced_dev_cart_m72.sh" ssh-ed25519 ...`
   Never overwrite existing authorized keys. Back up old authorized_keys and test the new key with **harmless denied** `hostname`, `scp`, `sftp` and `bash` attempts. Denial is expected; if any succeeds, immediately revoke the new key and stop.
4. Populate only dedicated Hostinger Dev environment secrets: `BYL_DEV_SSH_HOST`, `BYL_DEV_SSH_USER`, `BYL_DEV_SSH_PRIVATE_KEY`, and `BYL_DEV_SSH_KNOWN_HOSTS`. Do not put the key in repository-level, WWW, GitHub main source or old Desktop Commander configs. Keep `BYL_DEV_DEPLOY_ENABLED=false`. Verify **secret names/count only**, never values.
5. After separate explicit Owner authorization for an exact Dev release SHA, use `workflow_dispatch` with reviewed `apply` and a one-time controlled enablement window, then set the flag to false after completion. Validate the 3-file remote SHA receipt and M390/M1440 visual UAT. No shared production writes.

## Current stage

The earlier temporary Ed25519 public key was **revoked and tested to fail authentication**, with the previous `authorized_keys` backed up and restored; the associated Windows private key was deleted. GitHub Environment secrets remain 0 and deployment variable remains `false`. The existing two Dev deploy scripts remain off-webroot, unreachable from any dedicated key. A separate attempt to write GitHub Environment Secrets was blocked by platform security inspection and was **not retried through an alternative transport**. A subsequent authorized installation needs a new key and the new administrator-pinned SHA/time manifest in addition to review and CI.

This PR is a **security-hardening prerequisite**, not a migration or a checkout feature. Hostinger read-only baseline: user `u247343654`, SSH port `65002`, `~/.ssh/authorized_keys` exists/writable to user, environment `staging`. Existing Hostinger account is **shared** and the forced command is a critical least-privilege control; if Hostinger disables `authorized_keys command=` enforcement, DO NOT enable deployment. No keypair has been connected to GitHub at this stage, and no Dev/WWW files have been deployed.

Notes: avoid attempting another transport to bypass a prior security inspection denial. Key provisioning itself must pass the ordinary approved remote inspection.
