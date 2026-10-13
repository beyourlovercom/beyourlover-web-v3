#!/usr/bin/env bash
# Dev Cart M7.2 *remote* apply. REQUIRES separate authenticated, inspected and
# approved transport. This script grants no permission by itself.
set -euo pipefail
umask 077

[[ "$#" -eq 5 ]] || { echo "ARG_GATE_FAIL" >&2; exit 2; }
commit="$1"
bundle_sha="$2"
new_php="$3"
new_css="$4"
new_js="$5"
[[ "$commit" =~ ^[a-f0-9]{40}$ && "$bundle_sha" =~ ^[a-f0-9]{64}$ &&
   "$new_php" =~ ^[a-f0-9]{64}$ && "$new_css" =~ ^[a-f0-9]{64}$ &&
   "$new_js" =~ ^[a-f0-9]{64}$ ]] || { echo "SHA_GATE_FAIL" >&2; exit 2; }

BASE="/home/u247343654/domains/dev.beyourlover.com"
WP="$BASE/public_html"
THEME="$WP/wp-content/themes/beyourlover-v3"
BUNDLE="$BASE/.byl-dev-gate-cart-m7.2-$commit.zip"
BACKUP="$BASE/.byl-dev-gate-cart-m7.2-backup-$commit"
LOCK="$BASE/.byl-dev-gate-v1-lock"
rel=(
  "inc/cart-drawer.php"
  "assets/css/cart-drawer.css"
  "assets/js/cart-drawer.js"
)
old=(
  "ccb294d18116ce91d7339a474bc8e20006a3bcd42d76302a1f2efb3bb616468f"
  "6d81bbb19da8eee39c20167d9268f555f5cecd53f75cac02cf540d257bd7cca4"
  "e120f3fcfd54bba7b52d5b5614e9f54ebd1e4ffa280c29326362e7185ddebf85"
)
new=("$new_php" "$new_css" "$new_js")

[[ -d "$WP" && -d "$THEME" && -f "$BUNDLE" &&
   ! -e "$LOCK" && ! -e "$BACKUP" ]] ||
  { echo "DEV_ROOT_OR_LOCK_GATE_FAIL" >&2; exit 3; }
[[ "$(cd "$WP" && wp option get home --skip-themes --skip-plugins)" == "https://dev.beyourlover.com" ]] ||
  { echo "DEV_HOME_GATE_FAIL" >&2; exit 3; }
[[ "$(cd "$WP" && wp config get WP_ENVIRONMENT_TYPE)" == "staging" ]] ||
  { echo "STAGING_GATE_FAIL" >&2; exit 3; }
[[ "$(sha256sum "$BUNDLE" | cut -d' ' -f1)" == "$bundle_sha" ]] ||
  { echo "ZIP_DIGEST_GATE_FAIL" >&2; exit 3; }

# GitHub's trusted offline validator already checks the ZIP. Independently
# ensure remote member list contains exactly our three paths, no extra files.
actual_members="$(unzip -Z1 "$BUNDLE" | LC_ALL=C sort)"
expected_members="$(printf '%s\n' \
  'wp-content/themes/beyourlover-v3/inc/cart-drawer.php' \
  'wp-content/themes/beyourlover-v3/assets/css/cart-drawer.css' \
  'wp-content/themes/beyourlover-v3/assets/js/cart-drawer.js' | LC_ALL=C sort)"
[[ "$actual_members" == "$expected_members" ]] ||
  { echo "ZIP_ALLOWLIST_GATE_FAIL" >&2; exit 3; }

for i in 0 1 2; do
  path="$THEME/${rel[$i]}"
  [[ -f "$path" && ! -L "$path" &&
     "$(sha256sum "$path" | cut -d' ' -f1)" == "${old[$i]}" ]] ||
    { echo "LIVE_OLD_SHA_GATE_FAIL:${rel[$i]}" >&2; exit 3; }
done

mkdir -m 700 "$LOCK" || { echo "DEPLOY_LOCKED" >&2; exit 3; }
stage=""
backup_ready=0
applied=0
clean_up() {
  rc=$?
  if [[ "$applied" == "1" && "$backup_ready" == "1" ]]; then
    for i in 0 1 2; do
      cp -p "$BACKUP/${rel[$i]}" "$THEME/${rel[$i]}" || true
    done
    echo "AUTOMATIC_ROLLBACK_ATTEMPTED=YES"
  fi
  if [[ -n "$stage" ]]; then rm -rf -- "$stage"; fi
  rm -f -- "$BUNDLE"
  rmdir "$LOCK" 2>/dev/null || true
  exit "$rc"
}
trap clean_up EXIT
stage="$(mktemp -d "$BASE/.byl-dev-gate-stage-XXXXXXXX")"
for i in 0 1 2; do
  path="$stage/${rel[$i]}"
  mkdir -p "$(dirname "$path")"
  unzip -p "$BUNDLE" "wp-content/themes/beyourlover-v3/${rel[$i]}" > "$path"
  # umask 077 protects private staging, but publicly served theme files must
  # be readable by LiteSpeed after atomic replacement (Issue #93).
  chmod 0644 "$path"
  [[ "$(stat -c %a "$path")" == "644" ]] || { echo "STAGE_MODE_FAIL" >&2; exit 5; }
  [[ "$(sha256sum "$path" | cut -d' ' -f1)" == "${new[$i]}" ]] ||
    { echo "NEW_FILE_SHA_GATE_FAIL" >&2; exit 5; }
done
php -l "$stage/inc/cart-drawer.php" | grep -q "No syntax errors"

mkdir -m 700 "$BACKUP"
for i in 0 1 2; do
  mkdir -p "$BACKUP/$(dirname "${rel[$i]}")"
  cp -p "$THEME/${rel[$i]}" "$BACKUP/${rel[$i]}"
  [[ "$(sha256sum "$BACKUP/${rel[$i]}" | cut -d' ' -f1)" == "${old[$i]}" ]] || { echo "BACKUP_VERIFICATION_FAILED" >&2; exit 5; }
done
backup_ready=1
applied=1
for i in 0 1 2; do
  target="$THEME/${rel[$i]}"
  # Recheck live source immediately before replacing it.
  [[ "$(sha256sum "$target" | cut -d' ' -f1)" == "${old[$i]}" ]] ||
    { echo "CONCURRENT_CHANGE_GATE_FAIL" >&2; exit 5; }
  cp -p "$stage/${rel[$i]}" "$target.byl-incoming-$commit"
  mv -f "$target.byl-incoming-$commit" "$target"
  [[ "$(sha256sum "$target" | cut -d' ' -f1)" == "${new[$i]}" ]] ||
    { echo "POST_DEPLOY_SHA_FAIL" >&2; exit 6; }
  [[ "$(stat -c %a "$target")" == "644" ]] ||
    { echo "POST_DEPLOY_MODE_FAIL" >&2; exit 6; }
done
php -l "$THEME/inc/cart-drawer.php" | grep -q "No syntax errors"
for route in "/" "/?byl_cart_drawer_preview=1" "/checkout/"; do
  code="$(curl -sSL --max-time 15 -o /dev/null -w '%{http_code}' \
    "https://dev.beyourlover.com$route")"
  [[ "$code" == "200" ]] || { echo "POSTDEPLOY_HTTP_FAIL" >&2; exit 6; }
done
# Static resources must be accessible as well as PHP-rendered pages.
for asset in "/wp-content/themes/beyourlover-v3/assets/css/cart-drawer.css" \
             "/wp-content/themes/beyourlover-v3/assets/js/cart-drawer.js"; do
  code="$(curl -sSL --max-time 15 -o /dev/null -w '%{http_code}' \
    "https://dev.beyourlover.com$asset")"
  [[ "$code" == "200" ]] || { echo "POSTDEPLOY_ASSET_HTTP_FAIL:$asset" >&2; exit 6; }
done
applied=0
echo "DEV_ONLY_EXACT_THREE_FILES=PASS"
echo "DEPLOYED_GIT_SHA=$commit"
echo "ROLLBACK_BACKUP=$BACKUP"
echo "WWW_WRITES=0 ORDER_WRITES=0 PAYMENT_WRITES=0"
