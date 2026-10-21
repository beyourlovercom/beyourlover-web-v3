#!/usr/bin/env bash
# DEV ONLY. One-time stage with activation OFF and auditable snapshot/rollback.
# Args: <40-character exact-head> <64-character reviewed adapter SHA256>.
set -Eeuo pipefail
HEAD="$1"
EXPECTED="$2"
[[ "$HEAD" =~ ^[a-f0-9]{40}$ && "$EXPECTED" =~ ^[a-f0-9]{64}$ ]] ||
  { echo "FAIL exact HEAD or reviewed adapter SHA256"; exit 2; }
SITE="/home/u247343654/domains/dev.beyourlover.com/public_html"
THEME="$SITE/wp-content/themes/beyourlover-v3"
FUNC="$THEME/functions.php"
MANIFEST="$THEME/theme-os.json"
REST="$THEME/inc/dev-restworld-shipping-v1.php"
PILLOW="$THEME/inc/dev-pillow-oversized-shipping.php"
NEW="$THEME/inc/dev-global-special-shipping-v1.php"
WP="wp --path=$SITE --skip-plugins --skip-themes"
hash_file(){ sha256sum "$1" | cut -d' ' -f1; }
[[ "$($WP option get home)" == "https://dev.beyourlover.com" ]] ||
    { echo "FAIL wrong Dev site"; exit 2; }
[[ "$($WP option get stylesheet)" == "beyourlover-v3" ]] ||
    { echo "FAIL theme identity"; exit 2; }
[[ "$($WP option get byl_dev_restworld_shipping_active_v1)" == "yes" ]] ||
    { echo "FAIL Restworld adapter not active"; exit 2; }
GLOBAL_FLAG="$($WP option get byl_dev_global_special_shipping_active_v1 2>/dev/null || true)"
[[ "$GLOBAL_FLAG" != "yes" ]] || { echo "FAIL global policy already on"; exit 2; }
[[ ! -e "$NEW" ]] || { echo "FAIL global module already installed"; exit 2; }
ORIGINAL_FUNC_SHA="005f15c09ad2a4db5fffa0b2034acb9d84c802efb511b6a84eea39d2a8ecb727"
ORIGINAL_MANIFEST_SHA="f7dda1dc16335acaafdef56fc05739030ec3869829e1c7f2ba13f1b63d57dd0a"
[[ "$(hash_file "$FUNC")" == "$ORIGINAL_FUNC_SHA" ]] ||
    { echo "FAIL shared functions.php drift"; exit 2; }
[[ "$(hash_file "$MANIFEST")" == "$ORIGINAL_MANIFEST_SHA" ]] ||
    { echo "FAIL shared Theme OS drift"; exit 2; }
[[ "$(hash_file "$REST")" == "5d4ff69ba99149002c17f42abf242fb104cb4cb8ca22fcde8e5c071b9f9ce474" ]] ||
    { echo "FAIL Restworld code drift"; exit 2; }
[[ "$(hash_file "$PILLOW")" == "6f8c85e924d360e789a6dd919ba3733f23d809ff1f7469cb057e09ed127c70a2" ]] ||
    { echo "FAIL PILLOW code drift"; exit 2; }
umask 077
LOCK="$HOME/.byl-dev-global-special.stage.lock"
mkdir "$LOCK" || { echo "FAIL stage already locked"; exit 2; }
CHANGED=0
COMMITTED=0
BACKUP=""
on_exit() {
  status=$?
  if [[ "$CHANGED" == "1" && "$COMMITTED" != "1" ]]; then
    if [[ -n "$BACKUP" && -f "$BACKUP/functions.php" ]]; then cp -p "$BACKUP/functions.php" "$FUNC"; fi
    if [[ -n "$BACKUP" && -f "$BACKUP/theme-os.json" ]]; then cp -p "$BACKUP/theme-os.json" "$MANIFEST"; fi
    if [[ -f "$NEW" && "$(hash_file "$NEW")" == "$EXPECTED" ]]; then rm -f "$NEW"; fi
    echo "DEV_GLOBAL_SPECIAL_STAGE_ROLLBACK status=$status"
  fi
  rmdir "$LOCK" 2>/dev/null || true
}
trap on_exit EXIT
trap 'exit 130' INT
trap 'exit 143' TERM
BACKUP="$(mktemp -d "$HOME/.byl-dev-global-special-v1.XXXXXXXX")"
cp -p "$FUNC" "$BACKUP/functions.php"
cp -p "$MANIFEST" "$BACKUP/theme-os.json"
URL="https://raw.githubusercontent.com/beyourlovercom/beyourlover-web-v3/$HEAD/theme/beyourlover-v3/inc/dev-global-special-shipping-v1.php"
curl --fail --silent --show-error --location --proto '=https' --tlsv1.2 \
    --connect-timeout 8 --max-time 30 "$URL" -o "$BACKUP/global.next.php"
[[ "$(hash_file "$BACKUP/global.next.php")" == "$EXPECTED" ]] ||
    { echo "FAIL downloaded module hash mismatch"; exit 2; }
php -l "$BACKUP/global.next.php" >/dev/null
python3 - "$FUNC" "$MANIFEST" "$BACKUP" <<'PY'
from pathlib import Path
import json,sys
php,manifest,backup=map(Path,sys.argv[1:])
source=php.read_text()
anchor="require_once get_stylesheet_directory().'/inc/dev-restworld-shipping-v1.php';"
new="require_once get_stylesheet_directory().'/inc/dev-global-special-shipping-v1.php';"
assert source.count(anchor)==1 and new not in source, "PHP anchor mismatch"
(backup/'functions.next.php').write_text(source.replace(
    anchor,anchor+"\n// Dev-only global specials. Separately enabled by option; OFF at stage.\n"+new,1
))
text=manifest.read_text()
old='"inc/dev-restworld-shipping-v1.php",'
addition='"inc/dev-global-special-shipping-v1.php",'
assert text.count(old)==1 and addition not in text, "Manifest anchor mismatch"
updated=text.replace(old,old+"\n        "+addition,1)
decoded=json.loads(updated)
assert 'inc/dev-global-special-shipping-v1.php' in decoded['modules']['shipping-ui']['files']
(backup/'theme-os.next.json').write_text(updated)
PY
php -l "$BACKUP/functions.next.php" >/dev/null
python3 -m json.tool "$BACKUP/theme-os.next.json" >/dev/null
[[ "$(hash_file "$FUNC")" == "$ORIGINAL_FUNC_SHA" &&
   "$(hash_file "$MANIFEST")" == "$ORIGINAL_MANIFEST_SHA" &&
   ! -e "$NEW" ]] || { echo "FAIL shared Dev files raced"; exit 2; }
CHANGED=1
cp "$BACKUP/global.next.php" "$THEME/inc/.dev-global-special.pending"
mv "$THEME/inc/.dev-global-special.pending" "$NEW"
cp "$BACKUP/theme-os.next.json" "$THEME/.theme-os-global.pending"
mv "$THEME/.theme-os-global.pending" "$MANIFEST"
cp "$BACKUP/functions.next.php" "$THEME/.functions-global.pending"
mv "$THEME/.functions-global.pending" "$FUNC"
php -l "$NEW" >/dev/null
php -l "$FUNC" >/dev/null
[[ "$($WP option get byl_dev_global_special_shipping_active_v1 2>/dev/null || true)" != "yes" ]] ||
    { echo "FAIL option switched during staging"; exit 2; }
HTTP="$(curl -sS --max-time 20 -o /dev/null -w '%{http_code}' \
  'https://dev.beyourlover.com/wp-json/wc/store/v1/cart')"
[[ "$HTTP" == "200" ]] || { echo "FAIL Dev API $HTTP"; exit 2; }
COMMITTED=1
echo "DEV_GLOBAL_SPECIAL_STAGED_OFF=PASS"
echo "DEV_CART_API_HTTP=$HTTP"
echo "SOURCE_EXACT_HEAD=$HEAD"
echo "ADAPTER_SHA256=$(hash_file "$NEW")"
echo "DEV_BACKUP_PATH=$BACKUP"
echo "GLOBAL_FLAG_WAS_NOT_ENABLED=yes"
echo "WWW_GITHUB_MAIN_UNCHANGED=yes"
