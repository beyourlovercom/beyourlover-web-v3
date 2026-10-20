#!/usr/bin/env bash
# Dev-only staged Free Shipping after-coupon UAT adapter; WWW never touched.
set -Eeuo pipefail
HEAD_SHA="${1:-}"
[[ "$HEAD_SHA" =~ ^[a-f0-9]{40}$ ]] || { echo 'FAIL: require reviewed exact Git HEAD'; exit 2; }
SITE=/home/u247343654/domains/dev.beyourlover.com/public_html
THEME="$SITE/wp-content/themes/beyourlover-v3"
PHP="$THEME/functions.php"
MANIFEST="$THEME/theme-os.json"
NEW="$THEME/inc/dev-after-discount-free-shipping.php"
PILLOW="$THEME/inc/dev-pillow-oversized-shipping.php"
[[ -f "$PHP" && -f "$MANIFEST" && -f "$PILLOW" && ! -e "$NEW" ]] || { echo 'FAIL Dev theme/PILLOW preflight or already installed'; exit 2; }
URL="$(wp --path="$SITE" option get home)"
STYLE="$(wp --path="$SITE" option get stylesheet)"
ENVTYPE="$(wp --path="$SITE" eval 'echo wp_get_environment_type();')"
[[ "$URL" == 'https://dev.beyourlover.com' || "$URL" == 'https://dev.beyourlover.com/' ]] || { echo 'FAIL non-Dev site'; exit 2; }
[[ "$STYLE" == 'beyourlover-v3' && "$ENVTYPE" == 'staging' ]] || { echo 'FAIL Dev theme/environment mismatch'; exit 2; }
[[ "$(sha256sum "$PHP" | cut -d' ' -f1)" == 'a068a0119d39a253f6d80473e691955a0432ddeaa531f62070a6ba316d2c3795' ]] || { echo 'FAIL Dev functions.php drift'; exit 2; }
[[ "$(sha256sum "$MANIFEST" | cut -d' ' -f1)" == 'd9cabe0a438bd18c4656c98af7cd9deec079506416efac7eb1e467b5db82a281' ]] || { echo 'FAIL Dev Theme OS manifest drift'; exit 2; }
umask 077
BASE="$HOME/.byl-dev-shipping-discount-v1"
mkdir -p "$BASE"
BACKUP="$(mktemp -d "$BASE/release-XXXXXXXX")"
cp -p "$PHP" "$BACKUP/functions.original.php"
cp -p "$MANIFEST" "$BACKUP/manifest.original.json"
SRC="https://raw.githubusercontent.com/beyourlovercom/beyourlover-web-v3/$HEAD_SHA/theme/beyourlover-v3/inc/dev-after-discount-free-shipping.php"
curl --fail --silent --show-error --location --proto '=https' --tlsv1.2 --connect-timeout 8 --max-time 30 "$SRC" -o "$BACKUP/adapter.next.php"
php -l "$BACKUP/adapter.next.php" >/dev/null

python3 - "$PHP" "$MANIFEST" "$BACKUP" <<'PY'
import sys,json
from pathlib import Path
php,manifest,base=map(Path,sys.argv[1:])
s=php.read_text()
anchor="require_once get_stylesheet_directory().'/inc/dev-pillow-oversized-shipping.php';"
nxt="require_once get_stylesheet_directory().'/inc/dev-after-discount-free-shipping.php';"
assert s.count(anchor)==1 and nxt not in s,"Dev PHP include guard"
(base/"functions.next.php").write_text(s.replace(anchor,anchor+"\n// Dev-only post-coupon free shipping parity; staging-host guarded.\n"+nxt,1))
j=manifest.read_text()
needle='"inc/free-shipping-v2-policy.php",'
entry='"inc/dev-after-discount-free-shipping.php",'
assert j.count(needle)==1 and entry not in j,"Dev Theme manifest guard"
updated=j.replace(needle,needle+"\n        "+entry,1)
parsed=json.loads(updated)
assert "inc/dev-after-discount-free-shipping.php" in parsed["modules"]["shipping-ui"]["files"]
(base/"manifest.next.json").write_text(updated)
PY
php -l "$BACKUP/functions.next.php" >/dev/null
python3 -m json.tool "$BACKUP/manifest.next.json" >/dev/null
CHANGED=0
rollback() {
  status=$?
  if [[ "$CHANGED" == 1 ]]; then
    cp -p "$BACKUP/functions.original.php" "$PHP"
    cp -p "$BACKUP/manifest.original.json" "$MANIFEST"
    rm -f "$NEW"
    echo "DEV_DISCOUNT_SHIPPING_ROLLBACK status=$status"
  fi
  exit "$status"
}
trap rollback ERR
CHANGED=1
cp "$BACKUP/adapter.next.php" "$NEW"
cp "$BACKUP/functions.next.php" "$PHP"
cp "$BACKUP/manifest.next.json" "$MANIFEST"
php -l "$NEW" >/dev/null
php -l "$PHP" >/dev/null
wp --path="$SITE" eval 'if (!function_exists("byl_dev_after_discount_enabled") || !byl_dev_after_discount_enabled()) { throw new RuntimeException("Dev guard missing"); } echo "DEV_DISCOUNT_GUARD_PASS\n";'
HTTP="$(curl --silent --show-error --max-time 20 -o /dev/null -w '%{http_code}' 'https://dev.beyourlover.com/wp-json/wc/store/v1/cart')"
[[ "$HTTP" == '200' ]] || { echo "FAIL Dev Store API HTTP $HTTP"; false; }
trap - ERR
echo "DEV_DISCOUNT_DEPLOY_PASS exact_head=$HEAD_SHA Store_API_HTTP=$HTTP"
echo "BACKUP_PATH=$BACKUP"
echo "ADAPTER_SHA256=$(sha256sum "$NEW" | cut -d' ' -f1)"
echo 'ZERO_WWW_CHANGES_NO_ORDERS_NO_PAYMENTS'
