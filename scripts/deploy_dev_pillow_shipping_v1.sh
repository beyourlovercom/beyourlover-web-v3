#!/usr/bin/env bash
# Dev-only narrow BYL PILLOW V1 install. NEVER touches www.beyourlover.com.
# Requires a pinned, CI-approved 40-char GitHub commit SHA as first argument.
set -Eeuo pipefail
REF="${1:-}"
[[ "$REF" =~ ^[a-f0-9]{40}$ ]] || { echo "FAIL expected 40-char reviewed SHA"; exit 2; }

SITE="/home/u247343654/domains/dev.beyourlover.com/public_html"
THEME="$SITE/wp-content/themes/beyourlover-v3"
FILE="$THEME/inc/dev-pillow-oversized-shipping.php"
[[ -f "$THEME/functions.php" && -f "$THEME/theme-os.json" ]] || { echo "FAIL Dev theme missing"; exit 2; }

# Strict host/WP environment/site/active theme guard: fail closed. No WWW path.
HOME_URL="$(wp --path="$SITE" option get home)"
STYLE="$(wp --path="$SITE" option get stylesheet)"
ENV_TYPE="$(wp --path="$SITE" eval 'echo wp_get_environment_type();')"
[[ "$HOME_URL" == "https://dev.beyourlover.com" || "$HOME_URL" == "https://dev.beyourlover.com/" ]] || { echo "FAIL not Dev home"; exit 2; }
[[ "$STYLE" == "beyourlover-v3" && "$ENV_TYPE" == "staging" ]] || { echo "FAIL theme/environment guard"; exit 2; }
[[ ! -e "$FILE" ]] || { echo "FAIL already installed; do not overwrite"; exit 2; }

BASE="$HOME/.byl-dev-pillow-v1"
umask 077
mkdir -p "$BASE"
BACKUP="$(mktemp -d "$BASE/release-XXXXXXXX")"
cp -p "$THEME/functions.php" "$BACKUP/functions.php.original"
cp -p "$THEME/theme-os.json" "$BACKUP/theme-os.json.original"
SRC="https://raw.githubusercontent.com/beyourlovercom/beyourlover-web-v3/$REF/theme/beyourlover-v3/inc/dev-pillow-oversized-shipping.php"
curl --fail --silent --show-error --location --proto '=https' --tlsv1.2 --connect-timeout 8 --max-time 30 "$SRC" -o "$BACKUP/dev-pillow-oversized-shipping.php"
php -l "$BACKUP/dev-pillow-oversized-shipping.php" >/dev/null

python3 - "$THEME/functions.php" "$BACKUP/functions.php.next" "$THEME/theme-os.json" "$BACKUP/theme-os.json.next" <<'PY'
from pathlib import Path
import sys, json
original=Path(sys.argv[1]).read_text()
anchor="require_once get_stylesheet_directory().'/inc/theme-os-bootstrap.php';"
line="require_once get_stylesheet_directory().'/inc/dev-pillow-oversized-shipping.php';"
assert original.count(anchor)==1 and line not in original, "functions.php guard failed"
Path(sys.argv[2]).write_text(original.replace(anchor,anchor+"\n// Dev-only PILLOW $30 per unit; never enabled on WWW.\n"+line,1))
manifest=Path(sys.argv[3]).read_text()
marker='"inc/cart-drawer.php",'
assert manifest.count(marker)==1 and '"inc/dev-pillow-oversized-shipping.php"' not in manifest, "Theme OS ownership guard failed"
updated=manifest.replace(marker,marker+'\n        "inc/dev-pillow-oversized-shipping.php",',1)
parsed=json.loads(updated)
assert "inc/dev-pillow-oversized-shipping.php" in parsed["modules"]["cart"]["files"], "wrong module"
Path(sys.argv[4]).write_text(updated)
PY

php -l "$BACKUP/functions.php.next" >/dev/null
python3 -m json.tool "$BACKUP/theme-os.json.next" >/dev/null
CHANGED=0
rollback() {
    status=$?
    if [[ "$CHANGED" -eq 1 ]]; then
        cp -p "$BACKUP/functions.php.original" "$THEME/functions.php"
        cp -p "$BACKUP/theme-os.json.original" "$THEME/theme-os.json"
        rm -f "$FILE"
        echo "DEV_ROLLBACK_EXECUTED on error $status"
    fi
    exit "$status"
}
trap rollback ERR

# Install exactly one new file + two minimal Dev theme edits. No plugin config.
cp "$BACKUP/dev-pillow-oversized-shipping.php" "$FILE"
CHANGED=1
cp "$BACKUP/functions.php.next" "$THEME/functions.php"
cp "$BACKUP/theme-os.json.next" "$THEME/theme-os.json"
php -l "$THEME/functions.php" >/dev/null
php -l "$FILE" >/dev/null
wp --path="$SITE" eval 'if (!function_exists("byl_dev_pillow_shipping_enabled") || !byl_dev_pillow_shipping_enabled()) { throw new Exception("Dev site shipping guard not active"); } echo "DEV_PILLOW_GUARD_PASS\n";'
HTTP="$(curl --silent --show-error --location --max-time 20 --output /dev/null --write-out '%{http_code}' 'https://dev.beyourlover.com/cart/')"
[[ "$HTTP" == '200' ]] || { echo "FAIL Dev /cart HTTP $HTTP"; false; }
trap - ERR
echo "DEV_DEPLOY_PASS source=$REF HTTP_CART=$HTTP"
echo "BACKUP_PATH=$BACKUP"
echo "DEV_FUNCTIONS_SHA=$(sha256sum "$THEME/functions.php" | cut -d' ' -f1)"
echo "DEV_PILLOW_FILE_SHA=$(sha256sum "$FILE" | cut -d' ' -f1)"
echo "NO_WWW_ACTIONS; NO_ORDER_OR_PAYMENT"
