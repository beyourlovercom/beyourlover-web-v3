#!/usr/bin/env bash
# Dev-only, code-stage OFF; does NOT configure Woo rates or activate the policy.
set -Eeuo pipefail
HEAD="${1:-}"
EXPECTED_SHA="${2:-}"
[[ "$HEAD" =~ ^[a-f0-9]{40}$ && "$EXPECTED_SHA" =~ ^[a-f0-9]{64}$ ]] ||
    { echo "FAIL exact reviewed HEAD and adapter SHA-256 required"; exit 2; }
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
# A one-time Dev-only migration: fail safely on rerun, never overwrite an
# independently modified file or run two copies of this script together.
umask 077
LOCK="$HOME/.byl-dev-restworld-v1.stage.lock"
mkdir "$LOCK" || { echo "FAIL existing Dev stage lock"; exit 2; }
# Also release the lock if early preflight/download fails before full rollback is armed.
trap 'rmdir "$LOCK" 2>/dev/null || true' EXIT
stage_changed=0
stage_committed=0
BACKUP="$(mktemp -d "$HOME/.byl-dev-restworld-v1.XXXXXXXX")"
cp -p "$PHP" "$BACKUP/functions.original.php"
cp -p "$MANIFEST" "$BACKUP/theme-os.original.json"
SRC="https://raw.githubusercontent.com/beyourlovercom/beyourlover-web-v3/$HEAD/theme/beyourlover-v3/inc/dev-restworld-shipping-v1.php"
curl --fail --silent --show-error --location --proto '=https' --tlsv1.2 \
    --connect-timeout 8 --max-time 30 "$SRC" -o "$BACKUP/adapter.next.php"
[[ "$(hash_file "$BACKUP/adapter.next.php")" == "$EXPECTED_SHA" ]] ||
    { echo "FAIL pinned downloaded adapter SHA mismatch"; rmdir "$LOCK"; exit 2; }
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
# Use target-first, then manifest, functions LAST: until the final include
# becomes visible, this staged adapter is inert; explicit option is still OFF.
on_exit() {
    local status=$?
    if [[ "$stage_changed" == "1" && "$stage_committed" != "1" ]]; then
        cp -p "$BACKUP/functions.original.php" "$PHP" || true
        cp -p "$BACKUP/theme-os.original.json" "$MANIFEST" || true
        if [[ -f "$TARGET" && "$(hash_file "$TARGET")" == "$EXPECTED_SHA" ]]; then
            rm -f "$TARGET"
        fi
        echo "DEV_RESTWORLD_ROLLBACK status=$status"
    fi
    rmdir "$LOCK" 2>/dev/null || true
}
trap on_exit EXIT
trap 'exit 130' INT
trap 'exit 143' TERM
# Revalidate immediately before writing — other development windows may
# have changed shared theme files since our earlier read-only preflight.
[[ "$(hash_file "$PHP")" == "d478ca8ecc91d373000d2070e40e075c036ece9fb5cb55dbbc53c3da7d2109c8" ]] ||
    { echo "FAIL Dev functions.php race"; exit 2; }
[[ "$(hash_file "$MANIFEST")" == "4c09332052b3857beb9b13df9ac61b3211f18b5cf89d394e78b26482e3b68d3a" ]] ||
    { echo "FAIL Dev manifest race"; exit 2; }
stage_changed=1
cp "$BACKUP/adapter.next.php" "$THEME/inc/.dev-restworld-shipping-v1.php.pending"
mv "$THEME/inc/.dev-restworld-shipping-v1.php.pending" "$TARGET"
cp "$BACKUP/manifest.next.json" "$THEME/.theme-os.restworld.pending"
mv "$THEME/.theme-os.restworld.pending" "$MANIFEST"
cp "$BACKUP/functions.next.php" "$THEME/.functions.restworld.pending"
mv "$THEME/.functions.restworld.pending" "$PHP"
php -l "$PHP" >/dev/null
php -l "$TARGET" >/dev/null
HTTP="$(curl -sS --max-time 20 -o /dev/null -w '%{http_code}' \
    'https://dev.beyourlover.com/wp-json/wc/store/v1/cart')"
[[ "$HTTP" == "200" ]] || { echo "FAIL Dev Store API $HTTP"; exit 2; }
stage_committed=1
echo "DEV_RESTWORLD_CODE_STAGED_OFF exact_head=$HEAD HTTP=$HTTP"
echo "BACKUP_PATH=$BACKUP"
echo "ADAPTER_SHA256=$(hash_file "$TARGET")"
echo "DEV_FLAG=$CURRENT (not yes)"
echo "ZERO_WWW_CHANGES_NO_RATES_UPDATED"
