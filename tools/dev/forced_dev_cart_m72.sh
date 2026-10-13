#!/usr/bin/env bash
# Hostinger authorized_keys forced-command endpoint — NO generic shell/SFTP.
# Install outside webroot as ~/.byl-dev-gate/forced_dev_cart_m72.sh (0700).
# Only the SSH key with a matching restrict,command="..." entry can call it.
set -euo pipefail
umask 077
BASE=/home/u247343654/domains/dev.beyourlover.com
ENTRY=/home/u247343654/.byl-dev-gate/apply_dev_cart_m72.sh
MAX_ZIP_BYTES=1048576

# Every byte of SSH_ORIGINAL_COMMAND is treated as data (never eval/execute).
# GitHub Actions applies exactly ONE command with a ZIP via standard input.
[[ -z "${SSH_TTY:-}" ]] || { echo "SSH_PTY_DENIED" >&2; exit 4; }
read -r -a argv <<< "${SSH_ORIGINAL_COMMAND:-}"
[[ "${#argv[@]}" -eq 6 && "${argv[0]}" == "apply-cart-m72" ]] ||
  { echo "SSH_COMMAND_DENIED" >&2; exit 4; }
[[ "${argv[1]}" =~ ^[a-f0-9]{40}$ ]] ||
  { echo "SSH_COMMIT_DENIED" >&2; exit 4; }
for i in 2 3 4 5; do
  [[ "${argv[$i]}" =~ ^[a-f0-9]{64}$ ]] ||
    { echo "SSH_ARGUMENT_DENIED" >&2; exit 4; }
done
[[ -f "$ENTRY" && ! -L "$ENTRY" && -r "$ENTRY" ]] ||
  { echo "SIGNED_APPLY_SCRIPT_MISSING" >&2; exit 4; }
[[ -d "$BASE" ]] || { echo "DEV_ROOT_MISSING" >&2; exit 4; }

ZIP="$BASE/.byl-dev-gate-cart-m7.2-${argv[1]}.zip"
[[ ! -e "$ZIP" ]] || { echo "CANDIDATE_ZIP_ALREADY_EXISTS" >&2; exit 4; }
# noclobber prevents concurrent releases clobbering a staged archive.
( set -C; : > "$ZIP" ) || { echo "STAGING_CREATE_FAILED" >&2; exit 4; }
cleanup() { rm -f -- "$ZIP"; }
trap cleanup EXIT
head -c "$((MAX_ZIP_BYTES + 1))" > "$ZIP"
bytes="$(stat -c %s "$ZIP")"
[[ "$bytes" -gt 0 && "$bytes" -le "$MAX_ZIP_BYTES" ]] ||
  { echo "UPLOAD_SIZE_GATE_FAIL" >&2; exit 4; }
[[ "$(sha256sum "$ZIP" | cut -d' ' -f1)" == "${argv[2]}" ]] ||
  { echo "UPLOAD_SHA_GATE_FAIL" >&2; exit 4; }

# Script is installed by a separately authorized administrator, not by this key.
# It rechecks WordPress Dev identity, old/new hashes and performs rollback.
# No arbitrary remote shell, scp, sftp or file path is accepted.
bash "$ENTRY" "${argv[1]}" "${argv[2]}" "${argv[3]}" "${argv[4]}" "${argv[5]}"
