#!/usr/bin/env bash
# BYL Dev-only Store API presentation correction for PILLOW mixed carts.
# NEVER deploy to WWW. Requires an exact reviewed GitHub head.
set -Eeuo pipefail
REF="${1:-}"
[[ "$REF" =~ ^[a-f0-9]{40}$ ]] || { echo 'FAIL exact SHA required'; exit 2; }
SITE="/home/u247343654/domains/dev.beyourlover.com/public_html"
THEME="$SITE/wp-content/themes/beyourlover-v3"
FILE="$THEME/inc/dev-pillow-oversized-shipping.php"
[[ -f "$FILE" ]] || { echo 'FAIL Dev adapter missing'; exit 2; }
HOME_URL="$(wp --path="$SITE" option get home)"
STYLE="$(wp --path="$SITE" option get stylesheet)"
ENVIRONMENT="$(wp --path="$SITE" eval 'echo wp_get_environment_type();')"
[[ "$HOME_URL" == 'https://dev.beyourlover.com' || "$HOME_URL" == 'https://dev.beyourlover.com/' ]] || { echo 'FAIL host is not Dev'; exit 2; }
[[ "$STYLE" == 'beyourlover-v3' && "$ENVIRONMENT" == 'staging' ]] || { echo 'FAIL not Dev staging'; exit 2; }
[[ "$(sha256sum "$FILE" | cut -d' ' -f1)" == 'c89b6451598b939f100192d0506f259c189c26295a7de89643e40e8c8aabf6de' ]] || { echo 'FAIL Dev adapter checksum drift'; exit 2; }
umask 077
BASE="$HOME/.byl-dev-pillow-v1"
mkdir -p "$BASE"
BACKUP="$(mktemp -d "$BASE/promo-XXXXXXXX")"
cp -p "$FILE" "$BACKUP/adapter.previous.php"
curl --fail --silent --show-error --location --proto '=https' --tlsv1.2 --connect-timeout 8 --max-time 28 \
 "https://raw.githubusercontent.com/beyourlovercom/beyourlover-web-v3/$REF/theme/beyourlover-v3/inc/dev-pillow-oversized-shipping.php" \
 -o "$BACKUP/adapter.next.php"
php -l "$BACKUP/adapter.next.php" >/dev/null
grep -Fq 'byl_dev_pillow_shipping_promo_correct' "$BACKUP/adapter.next.php"
grep -Fq "add_filter('rest_post_dispatch'" "$BACKUP/adapter.next.php"
CHANGED=0
rollback() {
    status=$?
    if [[ "$CHANGED" -eq 1 ]]; then
        cp -p "$BACKUP/adapter.previous.php" "$FILE"
        echo "DEV_PILLOW_PROMO_ROLLBACK status=$status"
    fi
    exit "$status"
}
trap rollback ERR
CHANGED=1
cp "$BACKUP/adapter.next.php" "$FILE"
php -l "$FILE" >/dev/null
wp --path="$SITE" eval 'if (!function_exists("byl_dev_pillow_shipping_enabled") || !byl_dev_pillow_shipping_enabled() || !function_exists("byl_dev_pillow_shipping_promo_correct")) { throw new Exception("Dev-only promo code not loaded"); } echo "DEV_PILLOW_PROMO_GUARD_PASS\n";'
STATUS="$(curl --silent --show-error --max-time 20 --output /dev/null --write-out '%{http_code}' 'https://dev.beyourlover.com/cart/')"
[[ "$STATUS" == 200 ]] || { echo "FAIL Dev Cart HTTP $STATUS"; false; }
trap - ERR
echo "DEV_PILLOW_PROMO_DEPLOY_PASS head=$REF cart_http=$STATUS"
echo "BACKUP_PATH=$BACKUP"
echo "INSTALLED_SHA=$(sha256sum "$FILE" | cut -d' ' -f1)"
echo 'NO_WWW_NO_ORDER_NO_PAYMENT_NO_PLUGIN_CHANGES'
