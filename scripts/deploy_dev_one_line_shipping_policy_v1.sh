#!/usr/bin/env bash
# Dev-only, exact-head source copies and observed-current-hash guards.
# Scope: only 9 approved theme UI/policy files; no Woo option, order or plugin writes.
set -Eeuo pipefail
HEAD="$1"
[[ "$HEAD" =~ ^[a-f0-9]{40}$ ]] || { echo "FAIL exact commit required"; exit 2; }
SITE="/home/u247343654/domains/dev.beyourlover.com/public_html"
THEME="$SITE/wp-content/themes/beyourlover-v3"
WP="wp --path=$SITE --skip-plugins --skip-themes"
[[ "$($WP option get home)" == "https://dev.beyourlover.com" ]] ||
  { echo "DENIED site identity"; exit 2; }
[[ "$($WP option get stylesheet)" == "beyourlover-v3" ]] ||
  { echo "DENIED theme identity"; exit 2; }
hash_file(){ sha256sum "$1" | cut -d' ' -f1; }
# path | exact observed Dev SHA | code-review source SHA.
# Cart JS/CSS drift was line endings only; Checkout additional PILLOW copy
# sits entirely in the obsolete multi-line banner being intentionally replaced.
FILES="$(cat <<'LIST'
page-free-shipping.php|4e5c353d0bcb7ea6c26bacac676c28ee4c0c55c4f144dd6b1bb510b71486ced1|971be1e329bb404bb92f04b50dfa4287ca69058e0977865cc6dfc142d53a74cd
inc/free-shipping-v2-policy.php|e763fba6fc0371b83dca05ec87889a5bcd3a923566a7a245afc1c66600fce892|d16fd382fa619a5ef6cd40625cbcd6acce274457f11b06af1c5105d88f0031a1
assets/js/cart.js|dc461a580db29001312a0c4d11f284f86ac7870ad8025563433e993c88e62be8|0bd1f58afdabc451ccc8a4fddba214632f7138f058576bac65b90d3d9106c9fb
assets/js/checkout.js|d322aa9d9fe4351ee2868cea5e580d94ec9ffde59e7704e6c81a70076e9b5989|0552334a6ad83155c62c6aa89ed59c0d28fa6e98240513e53848c6764a383966
assets/js/cart-drawer.js|9de7138ccac219aa16b70be2549c74067b3f881858c5c573a46b3423a38f5271|9dd7233fb30e406070d91b697952d2d818a14b64ff662c10294c8b7aa0572c57
assets/js/free-shipping-v2.js|1a6aea6dbe7af8005da4862f841096d7ef37446e64d866d24834d2290ba169ca|488a79fb5fafe9074ea7820bccbaa598d6286f2ad422344ea7f95e2db242873c
assets/css/cart.css|5ed2ce0c17e042665b7a27a3f595ca27bb74c6189539825d9899079fc8a10305|a7d695259197307d214985200b197902a02a71ed458e5c3760751cae9ea57633
assets/css/checkout.css|598fc7d4ea6dafd082915de9a758969c367a6c4f4d412b8a7654bfe049206dd5|0eb7961c32f27d94e8c4e84d213747cfc906dcb6133c21f7a80541b3d28c073a
assets/css/cart-drawer.css|6d81bbb19da8eee39c20167d9268f555f5cecd53f75cac02cf540d257bd7cca4|3e71045824c58d27ed77d0e5edf9444737b6046f2131a110e3bc41861c13c87d
LIST
)"
# Check every file and exact latest observed version before making changes.
while IFS='|' read -r file before after; do
  [[ "$file" =~ ^[a-zA-Z0-9_./-]+$ && "$before" =~ ^[a-f0-9]{64}$ &&
     "$after" =~ ^[a-f0-9]{64}$ && -f "$THEME/$file" ]] ||
     { echo "FAIL file/sha allowlist $file"; exit 2; }
  [[ "$(hash_file "$THEME/$file")" == "$before" ]] ||
     { echo "FAIL Dev drift before stage $file"; exit 2; }
done <<< "$FILES"

umask 077
LOCK="$HOME/.byl-dev-shipping-copy-v1.lock"
mkdir "$LOCK" || { echo "FAIL Dev shipping copy stage already locked"; exit 2; }
BACKUP=""
CHANGED=0
COMMITTED=0
cleanup(){
  local status=$?
  if [[ "$CHANGED" == 1 && "$COMMITTED" != 1 ]]; then
    while IFS='|' read -r file before after; do
      if [[ -n "$BACKUP" && -f "$BACKUP/original/$file" ]]; then
        cp -p "$BACKUP/original/$file" "$THEME/$file" || true
      fi
    done <<< "$FILES"
    echo "DEV_SHIPPING_COPY_ROLLBACK_ATTEMPTED status=$status"
  fi
  rmdir "$LOCK" 2>/dev/null || true
}
trap cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' TERM
BACKUP="$(mktemp -d "$HOME/.byl-dev-shipping-copy-v1.XXXXXXXX")"
mkdir -p "$BACKUP/original" "$BACKUP/next"
while IFS='|' read -r file before after; do
  mkdir -p "$BACKUP/original/$(dirname "$file")" "$BACKUP/next/$(dirname "$file")"
  cp -p "$THEME/$file" "$BACKUP/original/$file"
  URL="https://raw.githubusercontent.com/beyourlovercom/beyourlover-web-v3/$HEAD/theme/beyourlover-v3/$file"
  curl --fail --silent --show-error --location --proto '=https' --tlsv1.2 \
    --connect-timeout 8 --max-time 30 "$URL" -o "$BACKUP/next/$file"
  [[ "$(hash_file "$BACKUP/next/$file")" == "$after" ]] ||
    { echo "FAIL source SHA mismatched $file"; exit 2; }
done <<< "$FILES"

php -l "$BACKUP/next/page-free-shipping.php" >/dev/null
php -l "$BACKUP/next/inc/free-shipping-v2-policy.php" >/dev/null
if command -v node >/dev/null 2>&1; then
  for file in assets/js/cart.js assets/js/checkout.js assets/js/cart-drawer.js assets/js/free-shipping-v2.js; do
    node --check "$BACKUP/next/$file" >/dev/null
  done
fi
# Recheck after staging in case another Checkout Dev window changed files.
while IFS='|' read -r file before after; do
  [[ "$(hash_file "$THEME/$file")" == "$before" ]] ||
    { echo "FAIL Dev drift during stage $file"; exit 2; }
done <<< "$FILES"
CHANGED=1
while IFS='|' read -r file before after; do
  pending="$THEME/$(dirname "$file")/.byl-shipping-copy-$(basename "$file").tmp"
  cp "$BACKUP/next/$file" "$pending"
  chmod --reference="$THEME/$file" "$pending"
  mv "$pending" "$THEME/$file"
  [[ "$(hash_file "$THEME/$file")" == "$after" ]] ||
    { echo "FAIL installed checksum $file"; exit 2; }
done <<< "$FILES"
php -l "$THEME/page-free-shipping.php" >/dev/null
php -l "$THEME/inc/free-shipping-v2-policy.php" >/dev/null
CART_API="$(curl -sS --max-time 20 -o /dev/null -w '%{http_code}' \
    'https://dev.beyourlover.com/wp-json/wc/store/v1/cart')"
COUNTRY_PAGE="$(curl -sS --max-time 20 -o /dev/null -w '%{http_code}' \
    'https://dev.beyourlover.com/free-shipping/')"
[[ "$CART_API" == "200" && "$COUNTRY_PAGE" == "200" ]] ||
  { echo "FAIL Dev health Cart=$CART_API policy=$COUNTRY_PAGE"; exit 2; }
COMMITTED=1
echo "DEV_ONE_LINE_SHIPPING_POLICY_DEPLOY_PASS"
echo "EXACT_HEAD=$HEAD"
echo "BACKUP_PATH=$BACKUP"
echo "CART_API_HTTP=$CART_API"
echo "COUNTRY_PAGE_HTTP=$COUNTRY_PAGE"
echo "WWW_GITHUB_MAIN_WOO_SETTINGS_UNCHANGED=yes"
