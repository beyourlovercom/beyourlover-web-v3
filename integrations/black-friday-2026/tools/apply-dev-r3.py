#!/usr/bin/env python3
"""BYL BF2026 Promotion UI V1 — guarded Dev deployment, never WWW.
Run only for the Owner-approved R0–R3 Dev integration.
"""
from __future__ import annotations

import argparse
import hashlib
import os
import shutil
import subprocess
from datetime import datetime, timezone
from pathlib import Path

EXPECTED_MAIN_SHA256 = "fb57d51946b1e9c74a7892f5494c5015c04fc601a75b4cd996465f4c13f0b6a3"
MODULE_SOURCE_COMMIT = "03d2c72be25313f71ec2ae3b930423f5fbd46152"
ANCHOR = "require_once __DIR__ . '/includes/class-byl-bf2026-r8-readiness.php';"
BOOTSTRAP = """// BYL_BF2026_PROMOTION_UI_R3: presentation-only module, switches default OFF.
require_once __DIR__ . '/includes/class-byl-bf2026-promotion-ui.php';
BYL_BF2026_Promotion_UI::boot();
"""
RELATIVE_FILES = (
    "includes/class-byl-bf2026-promotion-ui.php",
    "assets/promotion-ui.js",
    "assets/promotion-ui.css",
)


def sha(path: Path) -> str:
    return hashlib.sha256(path.read_bytes()).hexdigest()


def die(message: str) -> None:
    raise SystemExit("FAIL " + message)


def atomic_write(path: Path, data: bytes) -> None:
    tmp = path.with_name("." + path.name + ".byl-r3-new")
    tmp.write_bytes(data)
    os.replace(tmp, path)


def run() -> None:
    p = argparse.ArgumentParser()
    p.add_argument("--target", required=True)
    p.add_argument("--staging", required=True)
    p.add_argument("--backup-root", required=True)
    p.add_argument("--dry-run", action="store_true")
    args = p.parse_args()
    target = Path(args.target).resolve()
    staging = Path(args.staging).resolve()
    backup_root = Path(args.backup_root).resolve()
    if "/domains/dev.beyourlover.com/public_html/wp-content/plugins/black-friday-2026" not in str(target):
        die("target is not DEV black-friday-2026 plugin")
    if not (target.parent.parent.parent / "wp-load.php").exists():
        die("Dev WordPress root was not verified")
    main = target / "black-friday-2026.php"
    if sha(main) != EXPECTED_MAIN_SHA256:
        die(f"exact baseline mismatch: observed sha256={sha(main)}")
    original = main.read_text()
    if ANCHOR not in original or "0.2.3-dev-rc4" not in original:
        die("plugin boot or version baseline mismatch")
    if "BYL_BF2026_PROMOTION_UI_R3" in original:
        die("already patched")
    for rel in RELATIVE_FILES:
        source = staging / rel
        if not source.is_file() or source.stat().st_size < 150:
            die("missing/empty module " + rel)
        if (target / rel).exists():
            die("unexpected existing module path " + rel)
    php = staging / RELATIVE_FILES[0]
    check = subprocess.run(["php", "-l", str(php)], check=False, capture_output=True, text=True)
    if check.returncode:
        die("PHP lint failed: " + check.stdout + check.stderr)
    next_main = original.replace(
        ANCHOR, BOOTSTRAP + ANCHOR, 1
    ).replace("Version: 0.2.3-dev-rc4", "Version: 0.2.3-dev-rc4-promo-ui1", 1)
    if next_main == original:
        die("no main-file diff")
    if args.dry_run:
        print("PASS dry_run baseline_sha256=" + EXPECTED_MAIN_SHA256)
        print("PASS php_lint")
        print("PLAN copy " + ", ".join(RELATIVE_FILES))
        print("PLAN main_plugin_header_version=0.2.3-dev-rc4-promo-ui1")
        return
    stamp = datetime.now(timezone.utc).strftime("%Y%m%dT%H%M%SZ")
    backup = backup_root / ("bf2026-before-promo-ui-" + stamp)
    backup_root.mkdir(parents=True, exist_ok=True)
    shutil.copytree(target, backup, symlinks=True)
    if sha(backup / "black-friday-2026.php") != EXPECTED_MAIN_SHA256:
        die("backup readback failed")
    for rel in RELATIVE_FILES:
        destination = target / rel
        destination.parent.mkdir(parents=True, exist_ok=True)
        atomic_write(destination, (staging / rel).read_bytes())
    atomic_write(main, next_main.encode())
    if "BYL_BF2026_PROMOTION_UI_R3" not in main.read_text():
        die("main write readback failed; restore using backup=" + str(backup))
    print("PASS module_source_commit=" + MODULE_SOURCE_COMMIT)
    print("PASS backup=" + str(backup))
    print("PASS main_sha256=" + sha(main))
    for rel in RELATIVE_FILES:
        print("PASS " + rel + " sha256=" + sha(target / rel))
    print("INFO status DEFAULT OFF; activation requires separate Dev setting")
    print("INFO rollback command: cp -a " + str(backup) + "/. " + str(target) + "/")


if __name__ == "__main__":
    run()
