#!/usr/bin/env bash
# Targeted Dev-only PILLOW shipping safety patch; no WWW, no WP setting changes.
set -Eeuo pipefail
REF="${1:-}"
[[ "$REF" =~ ^[a-f0-9]{40}$ ]] || { echo 'FAIL requires reviewed exact HEAD'; exit 2; }
SITE="/home/u247343654/domains/dev.beyourlover.com/public_html"
THEME="$SITE/wp-content/themes/beyourlover-v3"
FILE="$THEME/inc/dev-pillow-oversized-shipping.php"
[[ -f "$FILE" ]] || { echo 'FAIL adapter not installed'; exit 2; }
HOME_URL="$(wp --path="$SITE" option get home)"
STYLE="$(wp --path="$SITE" option get stylesheet)"
ENV_TYPE="$(wp --path="$SITE" eval 'echo wp_get_environment_type();')"
[[ "$HOME_URL" == 'https://dev.beyourlover.com' || "$HOME_URL" == 'https://dev.beyourlover.com/' ]] || { echo 'FAIL non-Dev'; exit 2; }
[[ "$STYLE" == 'beyourlover-v3' && "$ENV_TYPE" == 'staging' ]] || { echo 'FAIL non-staging or unexpected theme'; exit 2; }
[[ "$(sha256sum "$FILE" | cut -d' ' -f1)" == '5b7a6d23d3fcbb779c7075863d32fd474e56939bba1b9957fe7985df0686459b' ]] || { echo 'FAIL adapter drift'; exit 2; }
umask 077
BASE="$HOME/.byl-dev-pillow-v1"
mkdir -p "$BASE"
BACKUP="$(mktemp -d "$BASE/fallback-XXXXXXXX")"
cp -p "$FILE" "$BACKUP/adapter.original.php"
curl --fail --silent --show-error --location --proto '=https' --tlsv1.2 --max-time 30 \
 "https://raw.githubusercontent.com/beyourlovercom/beyourlover-web-v3/$REF/theme/beyourlover-v3/inc/dev-pillow-oversized-shipping.php" \
 -o "$BACKUP/adapter.next.php"
php -l "$BACKUP/adapter.next.php" >/dev/null
grep -Fq 'if (!$rates)' "$BACKUP/adapter.next.php"
CHANGED=0
rollback() {
  status=$?
  if [[ "$CHANGED" -eq 1 ]]; then
    cp -p "$BACKUP/adapter.original.php" "$FILE"
    echo "DEV_FALLBACK_GUARD_ROLLBACK $status"
  fi
  exit "$status"
}
trap rollback ERR
CHANGED=1
cp "$BACKUP/adapter.next.php" "$FILE"
php -l "$FILE" >/dev/null
wp --path="$SITE" eval 'if (!function_exists("byl_dev_pillow_shipping_enabled") || !byl_dev_pillow_shipping_enabled()) { throw new Exception("Dev safety gate missing"); } echo "DEV_PILLOW_GUARD_PASS\n";'
trap - ERR
echo "DEV_FALLBACK_GUARD_DEPLOY_PASS SHA=$REF"
echo "BACKUP_PATH=$BACKUP"
echo "ADAPTER_SHA=$(sha256sum "$FILE" | cut -d' ' -f1)"
echo "NO_WWW_CHANGES_NO_ORDERS_NO_PAYMENTS"
