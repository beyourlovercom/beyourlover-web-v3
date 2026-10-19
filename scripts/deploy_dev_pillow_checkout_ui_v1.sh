#!/usr/bin/env bash
# Dev-only micro deployment: checkout free-shipping label parity with PILLOW rate V1.
# Requires exact 40-char reviewed GitHub PR HEAD; never touch WWW.
set -Eeuo pipefail
REF="${1:-}"
[[ "$REF" =~ ^[a-f0-9]{40}$ ]] || { echo 'FAIL exact reviewed head required'; exit 2; }
SITE="/home/u247343654/domains/dev.beyourlover.com/public_html"
THEME="$SITE/wp-content/themes/beyourlover-v3"
PHP="$THEME/inc/checkout.php"
JS="$THEME/assets/js/checkout.js"
PILLOW="$THEME/inc/dev-pillow-oversized-shipping.php"

[[ -f "$PHP" && -f "$JS" && -f "$PILLOW" ]] || { echo 'FAIL missing Dev PILLOW adapter or files'; exit 2; }
HOME_URL="$(wp --path="$SITE" option get home)"
STYLE="$(wp --path="$SITE" option get stylesheet)"
ENV_TYPE="$(wp --path="$SITE" eval 'echo wp_get_environment_type();')"
[[ "$HOME_URL" == 'https://dev.beyourlover.com' || "$HOME_URL" == 'https://dev.beyourlover.com/' ]] || { echo 'FAIL wrong host'; exit 2; }
[[ "$STYLE" == 'beyourlover-v3' && "$ENV_TYPE" == 'staging' ]] || { echo 'FAIL not Dev staging'; exit 2; }
# Prior Dev PHP/JS were independently confirmed byte-identical to audited main.
[[ "$(sha256sum "$PHP" | cut -d' ' -f1)" == '6ccf04c7bb3946b2c32d14ee150e67e4ff940080a612082f60031ba762033812' ]] || { echo 'FAIL Dev PHP drift'; exit 2; }
[[ "$(sha256sum "$JS" | cut -d' ' -f1)" == '1a29738b6ef84f768567a6568f040fbd64f9b00afd54160f9e593204c71d865f' ]] || { echo 'FAIL Dev JS drift'; exit 2; }

umask 077
BASE="$HOME/.byl-dev-pillow-v1"
mkdir -p "$BASE"
BACKUP="$(mktemp -d "$BASE/checkout-ui-XXXXXXXX")"
cp -p "$PHP" "$BACKUP/checkout.php.original"
cp -p "$JS" "$BACKUP/checkout.js.original"
SRC="https://raw.githubusercontent.com/beyourlovercom/beyourlover-web-v3/$REF/theme/beyourlover-v3"
curl --fail --silent --show-error --location --proto '=https' --tlsv1.2 --connect-timeout 8 --max-time 25 "$SRC/inc/checkout.php" -o "$BACKUP/checkout.php.next"
curl --fail --silent --show-error --location --proto '=https' --tlsv1.2 --connect-timeout 8 --max-time 25 "$SRC/assets/js/checkout.js" -o "$BACKUP/checkout.js.next"
php -l "$BACKUP/checkout.php.next" >/dev/null
grep -Fq 'byl-dev-pillow-package-source' "$BACKUP/checkout.php.next"
grep -Fq 'Oversized pillow shipping is $30 per item.' "$BACKUP/checkout.js.next"

CHANGED=0
rollback() {
  status=$?
  if [[ "$CHANGED" == 1 ]]; then
    cp -p "$BACKUP/checkout.php.original" "$PHP"
    cp -p "$BACKUP/checkout.js.original" "$JS"
    echo "DEV_CHECKOUT_UI_ROLLBACK on error: $status"
  fi
  exit "$status"
}
trap rollback ERR
CHANGED=1
cp "$BACKUP/checkout.php.next" "$PHP"
cp "$BACKUP/checkout.js.next" "$JS"
php -l "$PHP" >/dev/null
wp --path="$SITE" eval 'if (!function_exists("byl_dev_pillow_shipping_enabled") || !byl_dev_pillow_shipping_enabled()) { throw new Exception("PILLOW Dev guard missing"); } echo "PILLOW_DEV_GUARD_PASS\n";'
STATUS="$(curl --silent --show-error --max-time 20 --output /dev/null --write-out '%{http_code}' https://dev.beyourlover.com/checkout/)"
[[ "$STATUS" == 200 || "$STATUS" == 302 ]] || { echo "FAIL Dev checkout HTTP $STATUS"; false; }
trap - ERR
echo "DEV_CHECKOUT_UI_DEPLOY_PASS pinned_sha=$REF public_checkout_http=$STATUS"
echo "BACKUP_PATH=$BACKUP"
echo "PHP_SHA=$(sha256sum "$PHP" | cut -d' ' -f1)"
echo "JS_SHA=$(sha256sum "$JS" | cut -d' ' -f1)"
echo 'NO_WWW_ACTIONS_NO_ORDERS_NO_PAYMENTS'
