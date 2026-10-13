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

# IMPORTANT: A restricted key can still submit arbitrary PHP + self-chosen SHA
# unless the approved bytes are pinned independently by an ADMIN key.
# This manifest is created OFFLINE by a separately approved administrator,
# outside docroot and never by this forced-command key or GitHub Actions.
# Format: one exact line, six space-separated tokens:
# BYL-CART-M72-V1 <commit40> <zip64> <php64> <css64> <js64>
APPROVAL=/home/u247343654/.byl-dev-gate/approved-cart-m72-v1.txt
[[ -f "$APPROVAL" && ! -L "$APPROVAL" && -r "$APPROVAL" ]] ||
  { echo "ADMIN_APPROVAL_MISSING" >&2; exit 4; }
[[ "$(stat -c %u "$APPROVAL")" == "$(id -u)" &&
   "$(stat -c %a "$APPROVAL")" == "600" ]] ||
  { echo "ADMIN_APPROVAL_PERMISSIONS_FAIL" >&2; exit 4; }
mapfile -t approval_lines < "$APPROVAL"
[[ "${#approval_lines[@]}" -eq 1 ]] ||
  { echo "ADMIN_APPROVAL_FORMAT_FAIL" >&2; exit 4; }
read -r tag approved_commit approved_zip approved_php approved_css approved_js extra <<< "${approval_lines[0]}"
[[ "$tag" == "BYL-CART-M72-V1" && -z "${extra:-}" &&
   "$approved_commit" == "${argv[1]}" && "$approved_zip" == "${argv[2]}" &&
   "$approved_php" == "${argv[3]}" && "$approved_css" == "${argv[4]}" &&
   "$approved_js" == "${argv[5]}" ]] ||
  { echo "ADMIN_APPROVAL_MISMATCH" >&2; exit 4; }

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
