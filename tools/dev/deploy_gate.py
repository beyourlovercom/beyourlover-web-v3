#!/usr/bin/env python3
"""BYL Dev Deploy Gate V1: deterministic, offline, allowlisted *candidate packaging*.

This tool has NO SSH, file-transfer, WordPress write, restart, or deployment API.
GitHub and platform safety authorization are separate gates. Never use this
script as an alternative transport after a security inspection denial.
"""
from __future__ import annotations

import argparse
import hashlib
import json
from pathlib import Path, PurePosixPath
import re
import stat
import subprocess
import sys
import zipfile

THEME_PREFIX = "wp-content/themes/beyourlover-v3/"
ALLOWLIST = (
    "inc/cart-drawer.php",
    "assets/css/cart-drawer.css",
    "assets/js/cart-drawer.js",
)
# Confirmed *Dev*, never WWW, after Cart M7.2 exact release 2026-10-13.
EXPECTED_OLD = {
    "inc/cart-drawer.php": "ccb294d18116ce91d7339a474bc8e20006a3bcd42d76302a1f2efb3bb616468f",
    "assets/css/cart-drawer.css": "6d81bbb19da8eee39c20167d9268f555f5cecd53f75cac02cf540d257bd7cca4",
    "assets/js/cart-drawer.js": "e120f3fcfd54bba7b52d5b5614e9f54ebd1e4ffa280c29326362e7185ddebf85",
}
REQUIRED_SITE = "https://dev.beyourlover.com"
ZIP_TIME = (2020, 1, 1, 0, 0, 0)
MAX_SIZE = 1024 * 1024
SHA = re.compile(r"[a-f0-9]{40}\Z")
SHA256 = re.compile(r"[a-f0-9]{64}\Z")


class DeploymentGateError(ValueError):
    pass


def sha256(data: bytes) -> str:
    return hashlib.sha256(data).hexdigest()


def exact_git_head(repo: Path) -> str:
    try:
        commit = subprocess.check_output(
            ["git", "-C", str(repo), "rev-parse", "HEAD"], text=True
        ).strip()
        dirty = subprocess.check_output(
            ["git", "-C", str(repo), "status", "--porcelain", "--untracked-files=no"],
            text=True
        ).strip()
    except (subprocess.CalledProcessError, FileNotFoundError) as exc:
        raise DeploymentGateError("Git checkout unavailable") from exc
    if not SHA.fullmatch(commit) or dirty:
        raise DeploymentGateError("Git checkout is dirty or not an exact commit")
    return commit


def entry_path(rel: str) -> str:
    if (not isinstance(rel, str) or rel not in ALLOWLIST or "\\" in rel or
        PurePosixPath(rel).as_posix() != rel or
        any(x in ("", ".", "..") for x in rel.split("/"))):
        raise DeploymentGateError("Path is not in three-file allowlist")
    return THEME_PREFIX + rel


def make_receipt(repo: Path, exact_sha: str) -> tuple[dict, dict[str, bytes]]:
    if not SHA.fullmatch(exact_sha) or exact_git_head(repo) != exact_sha:
        raise DeploymentGateError("Requested SHA does not match clean checkout HEAD")
    files = {}
    payload = {}
    for rel in ALLOWLIST:
        path = repo / "theme" / "beyourlover-v3" / rel
        if path.is_symlink() or not path.is_file():
            raise DeploymentGateError("Missing or symlinked source: " + rel)
        body = path.read_bytes()
        if not body or len(body) > MAX_SIZE:
            raise DeploymentGateError("Source too large or empty: " + rel)
        if b"\x00" in body:
            raise DeploymentGateError("Unexpected binary data: " + rel)
        dst = entry_path(rel)
        payload[dst] = body
        files[rel] = {"destination": dst,
                      "expected_dev_old_sha256": EXPECTED_OLD[rel],
                      "candidate_new_sha256": sha256(body),
                      "bytes": len(body)}
    receipt = {
        "schema": 1,
        "kind": "BYL-DEV-ONLY-THREE-FILE-CANDIDATE",
        "target": REQUIRED_SITE,
        "environment": "staging",
        "repo": "beyourlovercom/beyourlover-web-v3",
        "exact_git_commit": exact_sha,
        "ci_result": "NOT_ATTESTED_BY_OFFLINE_BUILDER",
        "remote_apply_authorized": False,
        "contains_secrets": False,
        "allowlisted_files": files,
    }
    return receipt, payload


def build_package(repo: Path, exact_sha: str, out: Path) -> tuple[Path, Path]:
    receipt, payload = make_receipt(repo, exact_sha)
    if out.exists() and any(out.iterdir()):
        raise DeploymentGateError("Refuse to overwrite an existing output directory")
    out.mkdir(parents=True, exist_ok=True)
    package = out / "cart-m7.2-dev-only-candidate.zip"
    manifest = out / "cart-m7.2-dev-only-candidate.receipt.json"
    with zipfile.ZipFile(package, "x", compression=zipfile.ZIP_DEFLATED,
                         compresslevel=9) as z:
        for dst, body in sorted(payload.items()):
            info = zipfile.ZipInfo(dst, ZIP_TIME)
            info.compress_type = zipfile.ZIP_DEFLATED
            info.external_attr = (stat.S_IFREG | 0o644) << 16
            z.writestr(info, body, compress_type=zipfile.ZIP_DEFLATED,
                       compresslevel=9)
    receipt["zip_sha256"] = sha256(package.read_bytes())
    manifest.write_text(json.dumps(receipt, indent=2, sort_keys=True) + "\n",
                        encoding="utf-8")
    verify_package(package, manifest)
    return package, manifest


def verify_package(package: Path, manifest: Path) -> dict:
    receipt = json.loads(manifest.read_text(encoding="utf-8"))
    if (receipt.get("schema") != 1 or
        receipt.get("kind") != "BYL-DEV-ONLY-THREE-FILE-CANDIDATE" or
        receipt.get("target") != REQUIRED_SITE or
        receipt.get("environment") != "staging" or
        receipt.get("remote_apply_authorized") is not False or
        receipt.get("ci_result") != "NOT_ATTESTED_BY_OFFLINE_BUILDER" or
        not SHA.fullmatch(receipt.get("exact_git_commit", "")) or
        not SHA256.fullmatch(receipt.get("zip_sha256", "")) or
        sha256(package.read_bytes()) != receipt["zip_sha256"]):
        raise DeploymentGateError("Receipt mismatch / untrusted deploy assertion")
    files = receipt.get("allowlisted_files")
    if not isinstance(files, dict) or set(files) != set(ALLOWLIST):
        raise DeploymentGateError("Invalid package allowlist")
    with zipfile.ZipFile(package, "r") as z:
        infos = z.infolist()
        expected = {entry_path(rel) for rel in ALLOWLIST}
        if len(infos) != len(ALLOWLIST) or {i.filename for i in infos} != expected:
            raise DeploymentGateError("Extra/duplicate/missing archive entry")
        for rel in ALLOWLIST:
            info = z.getinfo(entry_path(rel))
            mode = info.external_attr >> 16
            if not stat.S_ISREG(mode) or info.file_size > MAX_SIZE:
                raise DeploymentGateError("Unsafe archive entry type")
            spec = files[rel]
            if (set(spec) != {"destination", "expected_dev_old_sha256",
                            "candidate_new_sha256", "bytes"} or
                spec["destination"] != entry_path(rel) or
                spec["expected_dev_old_sha256"] != EXPECTED_OLD[rel] or
                not SHA256.fullmatch(spec["candidate_new_sha256"])):
                raise DeploymentGateError("File/Dev-baseline evidence mismatch")
            body = z.read(info)
            if len(body) != spec["bytes"] or sha256(body) != spec["candidate_new_sha256"]:
                raise DeploymentGateError("Archive file digest mismatch")
    return receipt


def main() -> int:
    p = argparse.ArgumentParser(description=__doc__)
    sub = p.add_subparsers(dest="cmd", required=True)
    b = sub.add_parser("build", help="Build evidence only; no remote deployment")
    b.add_argument("--repo", type=Path, default=Path(__file__).resolve().parents[2])
    b.add_argument("--exact-sha", required=True)
    b.add_argument("--out", required=True, type=Path)
    v = sub.add_parser("verify", help="Verify allowlist, source & receipt SHA hashes")
    v.add_argument("--zip", required=True, type=Path)
    v.add_argument("--receipt", required=True, type=Path)
    args = p.parse_args()
    try:
        if args.cmd == "build":
            z, m = build_package(args.repo.resolve(), args.exact_sha, args.out)
            print("CANDIDATE_OFFLINE_BUILD=PASS")
            print("ZIP=", z, "\nRECEIPT=", m)
            print("REMOTE_DEPLOYMENT=NOT_AUTHORIZED_BY_THIS_TOOL")
        else:
            receipt = verify_package(args.zip, args.receipt)
            print("ALLOWLIST_RECEIPT_VERIFY=PASS")
            print("EXACT_SHA=" + receipt["exact_git_commit"])
            print("REMOTE_DEPLOYMENT=NOT_AUTHORIZED_BY_THIS_TOOL")
    except (DeploymentGateError, OSError, zipfile.BadZipFile,
            json.JSONDecodeError, KeyError, TypeError, ValueError) as exc:
        print("DEPLOY_GATE_FAIL_CLOSED:", str(exc), file=sys.stderr)
        return 2
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
