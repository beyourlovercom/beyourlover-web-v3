#!/usr/bin/env bash
# One-time security-authorized Hostinger setup of public-only, forced-command key.
# Private key NEVER reaches this server via this script.
set -euo pipefail
umask 077
BASE=/home/u247343654
PUB="$BASE/.byl-dev-gate/byl-dev-m72-id_ed25519.pub"
ENTRY="$BASE/.byl-dev-gate/forced_dev_cart_m72.sh"
AUTH="$BASE/.ssh/authorized_keys"
# Backup is unique to the new reviewed public key. Earlier safety canary
# backups remain untouched; refuse a duplicate key or repeat install.
[[ -f "$PUB" && ! -L "$PUB" && -f "$ENTRY" && -x "$ENTRY" ]] ||
  { echo "PUBLIC_KEY_OR_ENTRY_MISSING" >&2; exit 2; }
PUB_DIGEST="$(sha256sum "$PUB" | cut -d' ' -f1)"
[[ "$PUB_DIGEST" =~ ^[a-f0-9]{64}$ ]] ||
  { echo "PUBLIC_KEY_DIGEST_FAIL" >&2; exit 2; }
BACK="$BASE/.byl-dev-gate/authorized_keys-pre-m72-${PUB_DIGEST:0:20}"
[[ -f "$AUTH" && ! -L "$AUTH" && -w "$AUTH" && ! -e "$BACK" ]] ||
  { echo "AUTHORIZED_KEYS_BASELINE_FAIL" >&2; exit 2; }
read -r typ key comment < "$PUB"
[[ "$typ" == "ssh-ed25519" && "$key" =~ ^[A-Za-z0-9+/=]+$ &&
   "$comment" == "BYL-DEV-GATE-M72-20261013" ]] ||
  { echo "PUBLIC_KEY_FORMAT_FAIL" >&2; exit 2; }
if grep -Fq "BYL-DEV-GATE-M72-20261013" "$AUTH"; then
  echo "DUPLICATE_KEY_REFUSED" >&2
  exit 2
fi
cp -p "$AUTH" "$BACK"
tmp="$(mktemp "$BASE/.ssh/authorized_keys.m72.XXXXXXXX")"
trap 'rm -f -- "$tmp"' EXIT
cat "$AUTH" > "$tmp"
printf 'restrict,command="%s" %s %s %s\n' "$ENTRY" "$typ" "$key" "$comment" >> "$tmp"
chmod 600 "$tmp"
mv "$tmp" "$AUTH"
echo "BYL_RESTRICTED_PUBLIC_KEY_REGISTERED=PASS"
echo "ORIGINAL_AUTHORIZED_KEYS_BACKUP_CREATED=YES"
ssh-keygen -lf "$PUB"
