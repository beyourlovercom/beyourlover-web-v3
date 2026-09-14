#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
find theme platform -type f -name '*.php' -print0 | xargs -0 -n1 php -l
python3 -m py_compile scripts/*.py
python3 scripts/check_migration_ids.py
git diff --check
echo "FOUNDATION_VERIFY=PASS"
