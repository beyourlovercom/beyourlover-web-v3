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

## Setup protocol (separate authorization and actual device inspection)

1. Verify the GitHub Actions environment is still only `main`, zero secrets and deployment flag `false`; verify destination `home` is exactly `https://dev.beyourlover.com` and `WP_ENVIRONMENT_TYPE=staging`.
2. Obtain a separately generated Ed25519 deploy key pair, private key readable only by the operator and stored **exclusively** as the GitHub Environment secret `BYL_DEV_SSH_PRIVATE_KEY`; **never** expose it in chat, command logs, repo, artifacts or stdout. Pin the remote SSH host key against the **already trusted** Windows/Owner known_hosts record (not unauthenticated ssh-keyscan alone).
3. On Hostinger through the existing **safety-authorized admin** access, install the exact reviewed two scripts outside all webroots with `0700` directory and scripts; install the public key into `~/.ssh/authorized_keys` with OpenSSH options:
   `restrict,command="/home/u247343654/.byl-dev-gate/forced_dev_cart_m72.sh" ssh-ed25519 ...`
   Never overwrite existing authorized keys. Back up old authorized_keys and test the new key with **harmless denied** `hostname`, `scp`, `sftp` and `bash` attempts. Denial is expected; if any succeeds, immediately revoke the new key and stop.
4. Populate only dedicated Hostinger Dev environment secrets: `BYL_DEV_SSH_HOST`, `BYL_DEV_SSH_USER`, `BYL_DEV_SSH_PRIVATE_KEY`, and `BYL_DEV_SSH_KNOWN_HOSTS`. Do not put the key in repository-level, WWW, GitHub main source or old Desktop Commander configs. Keep `BYL_DEV_DEPLOY_ENABLED=false`. Verify **secret names/count only**, never values.
5. After separate explicit Owner authorization for an exact Dev release SHA, use `workflow_dispatch` with reviewed `apply` and a one-time controlled enablement window, then set the flag to false after completion. Validate the 3-file remote SHA receipt and M390/M1440 visual UAT. No shared production writes.

## Current stage

This PR is a **security-hardening prerequisite**, not a migration or a checkout feature. Hostinger read-only baseline: user `u247343654`, SSH port `65002`, `~/.ssh/authorized_keys` exists/writable to user, environment `staging`. Existing Hostinger account is **shared** and the forced command is a critical least-privilege control; if Hostinger disables `authorized_keys command=` enforcement, DO NOT enable deployment. No keypair has been connected to GitHub at this stage, and no Dev/WWW files have been deployed.

Notes: avoid attempting another transport to bypass a prior security inspection denial. Key provisioning itself must pass the ordinary approved remote inspection.
