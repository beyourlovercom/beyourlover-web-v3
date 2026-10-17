#!/usr/bin/env python3
"""B2 Business Partnerships offline-only Dev candidate; NEVER deploys or connects.
A strict allowlist ZIP for existing Theme OS installations, not an installable theme.
"""
from __future__ import annotations
import argparse
import hashlib
import json
import re
import subprocess
import sys
import zipfile
from pathlib import Path

ALLOWLIST = (
    "page-business.php",
    "assets/css/business.css",
    "assets/js/business.js",
)
SOURCE_PREFIX = Path("theme/beyourlover-v3")
ZIP_NAME = "byl-business-b2-dev-only-THREE-FILES.zip"
RECEIPT_NAME = "byl-business-b2-dev-only.receipt.json"

def sha256(data: bytes) -> str:
    return hashlib.sha256(data).hexdigest()

def git_sha(source: Path) -> str:
    process = subprocess.run(
        ["git", "-C", str(source), "rev-parse", "HEAD"],
        check=True, text=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE,
    )
    return process.stdout.strip()

def verify_source(source: Path, exact_sha: str) -> dict[str, bytes]:
    if not re.fullmatch(r"[a-f0-9]{40}", exact_sha):
        raise ValueError("Exact SHA must be forty lowercase hex characters")
    if git_sha(source) != exact_sha:
        raise ValueError("Exact SHA mismatch; candidate source rejected")
    root = (source / SOURCE_PREFIX).resolve()
    if not root.is_dir():
        raise ValueError("Theme OS source missing")
    data = {}
    for filename in ALLOWLIST:
        candidate = root / filename
        if candidate.is_symlink() or not candidate.is_file() or root not in candidate.resolve().parents:
            raise ValueError("Unsafe or missing theme candidate path: " + filename)
        data[filename] = candidate.read_bytes()
    if b"dev.beyourlover.com" not in data["page-business.php"]:
        raise ValueError("Dev-host guard missing")
    return data

def build(source: Path, exact_sha: str, out: Path) -> None:
    data = verify_source(source, exact_sha)
    out.mkdir(parents=True, exist_ok=True)
    destination = out / ZIP_NAME
    with zipfile.ZipFile(destination, "w", compression=zipfile.ZIP_STORED) as archive:
        for filename in ALLOWLIST:
            info = zipfile.ZipInfo(filename=filename, date_time=(1980, 1, 1, 0, 0, 0))
            info.compress_type = zipfile.ZIP_STORED
            info.create_system = 3
            info.external_attr = 0o100644 << 16
            archive.writestr(info, data[filename])
    receipt = {
        "schema": "byl.business.b2.dev-candidate.v1",
        "source_sha": exact_sha,
        "scope": "EXACTLY THREE FILES, DEV ONLY. No remote apply.",
        "target": "wp-content/themes/beyourlover-v3/",
        "allowlisted_files": {key: sha256(data[key]) for key in ALLOWLIST},
        "zip_sha256": sha256(destination.read_bytes()),
    }
    (out / RECEIPT_NAME).write_text(json.dumps(receipt, indent=2, sort_keys=True) + "\n", encoding="utf-8")
    print("B2_DEV_CANDIDATE_BUILD=PASS ZIP_SHA256=" + receipt["zip_sha256"])

def verify(out: Path) -> None:
    receipt = json.loads((out / RECEIPT_NAME).read_text(encoding="utf-8"))
    if receipt.get("schema") != "byl.business.b2.dev-candidate.v1":
        raise ValueError("Unexpected candidate receipt schema")
    zipdata = (out / ZIP_NAME).read_bytes()
    if sha256(zipdata) != receipt["zip_sha256"]:
        raise ValueError("Candidate ZIP checksum mismatch")
    if set(receipt["allowlisted_files"]) != set(ALLOWLIST):
        raise ValueError("Unexpected allowlist")
    with zipfile.ZipFile(out / ZIP_NAME) as archive:
        if archive.namelist() != list(ALLOWLIST):
            raise ValueError("ZIP must contain exactly three permitted theme files")
        for item in archive.infolist():
            if item.is_dir() or item.filename.startswith("/") or ".." in Path(item.filename).parts:
                raise ValueError("Unsafe ZIP member")
            if sha256(archive.read(item.filename)) != receipt["allowlisted_files"][item.filename]:
                raise ValueError("Payload SHA mismatch: " + item.filename)
    print("B2_DEV_CANDIDATE_VERIFY=PASS SHA256=" + receipt["zip_sha256"])

def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("action", choices=["build", "verify"])
    parser.add_argument("--source", type=Path, default=Path("."))
    parser.add_argument("--exact-sha", default="")
    parser.add_argument("--out", type=Path, required=True)
    args = parser.parse_args()
    if args.action == "build":
        build(args.source, args.exact_sha, args.out)
    else:
        verify(args.out)

if __name__ == "__main__":
    try:
        main()
    except (OSError, ValueError, subprocess.CalledProcessError, KeyError, zipfile.BadZipFile) as error:
        print("B2_DEV_CANDIDATE_FAIL: " + str(error), file=sys.stderr)
        sys.exit(1)
