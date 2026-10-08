#!/usr/bin/env bash
set -euo pipefail
umask 077
WP=/home/u247343654/domains/dev.beyourlover.com/public_html
ST=/home/u247343654/byl-v3-staging
B=/home/u247343654/byl-v3-backups/p23-positive-lookup-20261008
test ! -e "$B" || { echo "STOP_EXISTING_BACKUP"; exit 3; }
mkdir -m 700 "$B"
trap 'rm -f "$B/.backup-client.cnf"' EXIT
cd "$WP"
test "$(wp option get siteurl)" = 'https://dev.beyourlover.com'
test ! -e "$ST/p13-sandbox-gate.json"
wp eval-file "$ST/p23-db-private-config.php"
DBNAME=$(wp config get DB_NAME)
mysqldump --defaults-extra-file="$B/.backup-client.cnf" --no-tablespaces --single-transaction --quick --skip-lock-tables "$DBNAME" > "$B/dev-db-before.sql"
chmod 600 "$B/dev-db-before.sql"
test -s "$B/dev-db-before.sql"
wp plugin list --fields=name,status,version --format=csv > "$B/plugins-before.csv"
wp eval 'echo count(wc_get_orders(["limit"=>-1,"return"=>"ids"])),PHP_EOL;' > "$B/order-count-before.txt"
echo "P23_DB_BACKUP=PASS"
echo "BACKUP_MB=$(du -m "$B/dev-db-before.sql" | cut -f1)"
echo "TABLES=$(grep -c 'CREATE TABLE' "$B/dev-db-before.sql" || true)"
echo "PATH=$B"
