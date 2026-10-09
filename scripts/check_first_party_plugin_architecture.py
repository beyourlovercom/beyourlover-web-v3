#!/usr/bin/env python3
"""Owner gate: no third first-party BYL business plugin can be introduced.

BYL Platform is a frozen transitional rollback source, never a new feature home.
After verified Dev retirement the entire legacy directory may be removed.
"""
from pathlib import Path
from hashlib import sha256

root = Path("platform")
legacy = root / "byl-platform"
FROZEN_FILES = {
    'platform/byl-platform/byl-platform.php': '5ddd5c68c898425f88dd4da0851ac2e8ff5672a47d1d9b5fc326a01f1a0e0771',
    'platform/byl-platform/includes/class-byl-cli.php': 'e2726a911f8294cd7638bd213617040348107c20371ea10daeaf2bbec2672bea',
    'platform/byl-platform/includes/class-byl-migration-runner.php': 'f8639f3c9bbd6c740cccf925176c49608d0d010941d68f8b7694d183e123a675',
    'platform/byl-platform/includes/class-byl-shipping-promotion-policy.php': '8500d2139a88dc22fc80fb0ef69c7271f18b082de1fa544151a787013efa9671',
    'platform/byl-platform/includes/class-byl-shipping-promotion-runtime.php': '8b6e20f41aeab04757659ceba739269aa48c15e009075f837cfdad58964afa48',
    'platform/byl-platform/migrations/V3_DB_001_Baseline.php': '673b459d93876826fe3e858caf57cdf431052475c2774e241f59bf8018d79d23',
}
unexpected = []
if root.exists():
    unexpected.extend(str(p) for p in root.iterdir() if p.name != "byl-platform")
if not legacy.exists():
    if unexpected:
        raise SystemExit("FAIL: new first-party plugin directory forbidden: " + str(unexpected))
    print("PASS: BYL Platform retired; no third first-party business plugin")
else:
    current = {str(p): sha256(p.read_bytes()).hexdigest()
               for p in legacy.rglob("*") if p.is_file()}
    if current != FROZEN_FILES or unexpected:
        raise SystemExit("FAIL: frozen BYL Platform changed or new first-party plugin added. "
                         "Move business features to Andy Commerce instead.")
    print("PASS: BYL Platform frozen pending Dev UAT; no new first-party plugin")
