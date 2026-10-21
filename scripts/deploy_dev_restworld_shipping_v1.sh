#!/usr/bin/env bash
# Dev-only, code-stage OFF; does NOT configure Woo rates or activate the policy.
set -Eeuo pipefail
HEAD="${1:-}"
[[ "$HEAD" =~ ^[a-f0-9]{40}$ ]] || { echo "FAIL exact reviewed head required"; exit 2; }
SITE="/home/u247343654/domains/dev.beyourlover.com/public_html"
THEME="$SITE/wp-content/themes/beyourlover-v3"
PHP="$THEME/functions.php"
MANIFEST="$THEME/theme-os.json"
PILLOW="$THEME/inc/dev-pillow-oversized-shipping.php"
DISCOUNT="$THEME/inc/dev-after-discount-free-shipping.php"
TARGET="$THEME/inc/dev-restworld-shipping-v1.php"
WP="wp --path=$SITE --skip-plugins --skip-themes"
[[ -f "$PHP" && -f "$MANIFEST" && -f "$PILLOW" && -f "$DISCOUNT" && ! -e "$TARGET" ]] ||
    { echo "FAIL Dev preflight files or new adapter already exists"; exit 2; }
HOME_URL="$($WP option get home)"
STYLE="$($WP option get stylesheet)"
[[ "$HOME_URL" == "https://dev.beyourlover.com" || "$HOME_URL" == "https://dev.beyourlover.com/" ]] ||
    { echo "FAIL site identity"; exit 2; }
[[ "$STYLE" == "beyourlover-v3" ]] || { echo "FAIL active theme"; exit 2; }
hash_file() { sha256sum "$1" | cut -d' ' -f1; }
# Protect every pre-existing Dev theme integration — do not overwrite another
# Checkout B/window's edits or a newly changed shared file.
[[ "$(hash_file "$PHP")" == "d478ca8ecc91d373000d2070e40e075c036ece9fb5cb55dbbc53c3da7d2109c8" ]] ||
    { echo "FAIL Dev functions.php drift"; exit 2; }
[[ "$(hash_file "$MANIFEST")" == "4c09332052b3857beb9b13df9ac61b3211f18b5cf89d394e78b26482e3b68d3a" ]] ||
    { echo "FAIL Dev Theme OS manifest drift"; exit 2; }
[[ "$(hash_file "$PILLOW")" == "6f8c85e924d360e789a6dd919ba3733f23d809ff1f7469cb057e09ed127c70a2" ]] ||
    { echo "FAIL Dev PILLOW adapter drift"; exit 2; }
[[ "$(hash_file "$DISCOUNT")" == "148cb3b8320eae5fde67780cfbda5ad6ce602c8d20bd7dfc1acc89d299e86b76" ]] ||
    { echo "FAIL Dev coupon adapter drift"; exit 2; }
CURRENT="$($WP option get byl_dev_restworld_shipping_active_v1 2>/dev/null || true)"
[[ "$CURRENT" != "yes" ]] || { echo "FAIL already active; do not re-stage"; exit 2; }
umask 077
BACKUP="$(mktemp -d "$HOME/.byl-dev-restworld-v1.XXXXXXXX")"
cp -p "$PHP" "$BACKUP/functions.original.php"
cp -p "$MANIFEST" "$BACKUP/theme-os.original.json"
SRC="https://raw.githubusercontent.com/beyourlovercom/beyourlover-web-v3/$HEAD/theme/beyourlover-v3/inc/dev-restworld-shipping-v1.php"
curl --fail --silent --show-error --location --proto '=https' --tlsv1.2 \
    --connect-timeout 8 --max-time 30 "$SRC" -o "$BACKUP/adapter.next.php"
php -l "$BACKUP/adapter.next.php" >/dev/null
python3 - "$PHP" "$MANIFEST" "$BACKUP" <<'PY'
from pathlib import Path
import json,sys
php,manifest,folder=map(Path,sys.argv[1:])
source=php.read_text()
anchor="require_once get_stylesheet_directory().'/inc/dev-after-discount-free-shipping.php';"
add="require_once get_stylesheet_directory().'/inc/dev-restworld-shipping-v1.php';"
assert source.count(anchor)==1 and add not in source
p=source.replace(anchor,anchor+"\n// Dev-only, activation OFF until verified staging rule rollout.\n"+add,1)
(folder/"functions.next.php").write_text(p)
text=manifest.read_text()
entry='"inc/dev-after-discount-free-shipping.php",'
new='"inc/dev-restworld-shipping-v1.php",'
assert text.count(entry)==1 and new not in text
updated=text.replace(entry,entry+"\n        "+new,1)
parsed=json.loads(updated)
assert "inc/dev-restworld-shipping-v1.php" in parsed["modules"]["shipping-ui"]["files"]
(folder/"manifest.next.json").write_text(updated)
PY
php -l "$BACKUP/functions.next.php" >/dev/null
python3 -m json.tool "$BACKUP/manifest.next.json" >/dev/null
changed=0
rollback() {
    local status=$?
    if [[ "$changed" == "1" ]]; then
        cp -p "$BACKUP/functions.original.php" "$PHP"
        cp -p "$BACKUP/theme-os.original.json" "$MANIFEST"
        rm -f "$TARGET"
        echo "DEV_RESTWORLD_ROLLBACK status=$status"
    fi
    exit "$status"
}
trap rollback ERR
changed=1
cp "$BACKUP/adapter.next.php" "$TARGET"
cp "$BACKUP/functions.next.php" "$PHP"
cp "$BACKUP/manifest.next.json" "$MANIFEST"
php -l "$PHP" >/dev/null
php -l "$TARGET" >/dev/null
HTTP="$(curl -sS --max-time 20 -o /dev/null -w '%{http_code}' \
    'https://dev.beyourlover.com/wp-json/wc/store/v1/cart')"
[[ "$HTTP" == "200" ]] || { echo "FAIL Dev Store API $HTTP"; false; }
trap - ERR
echo "DEV_RESTWORLD_CODE_STAGED_OFF exact_head=$HEAD HTTP=$HTTP"
echo "BACKUP_PATH=$BACKUP"
echo "ADAPTER_SHA256=$(hash_file "$TARGET")"
echo "DEV_FLAG=$CURRENT (not yes)"
echo "ZERO_WWW_CHANGES_NO_RATES_UPDATED"
