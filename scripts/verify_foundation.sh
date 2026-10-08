#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
find theme platform -type f -name '*.php' -print0 | xargs -0 -n1 php -l
python3 -m py_compile scripts/*.py
python3 scripts/check_migration_ids.py
python3 scripts/test_shipping_promotion_contract.py
python3 scripts/test_shipping_promotion_runtime_contract.py
python3 scripts/test_checkout_shipping_promotion_docs.py
python3 scripts/test_cart_promotion_ux_contract.py
python3 scripts/test_checkout_progressive_ux_contract.py
git diff --check
echo "FOUNDATION_VERIFY=PASS"
