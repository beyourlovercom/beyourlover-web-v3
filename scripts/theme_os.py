#!/usr/bin/env python3
"""BYL Theme OS V1: fail-closed module ownership and deterministic theme-only release.

This tool NEVER installs a WordPress theme, changes an option, or deploys code.
"""
from __future__ import annotations

import argparse
import hashlib
import json
from pathlib import Path, PurePosixPath
import re
import subprocess
import sys
import zipfile

ROOT = Path(__file__).resolve().parent.parent
THEME = ROOT / "theme" / "beyourlover-v3"
MANIFEST_FILE = THEME / "theme-os.json"
ZIP_STAMP = (2020, 1, 1, 0, 0, 0)
REQUIRED = {"foundation", "shell", "cart", "checkout", "product", "postpurchase"}
BLOCKED = {".git", ".env", "vendor", "node_modules", "uploads", "wp-config.php", ".DS_Store"}
MODULE_TYPES = {"foundation", "presentation", "commerce-ui", "protected-commerce-ui"}


class ThemeOSViolation(ValueError):
    pass


def digest(data: bytes) -> str:
    return hashlib.sha256(data).hexdigest()


def git_head() -> str:
    return subprocess.check_output(["git", "rev-parse", "HEAD"], cwd=ROOT, text=True).strip()


def clean_git() -> bool:
    # Ignore harmless generated tooling outputs (e.g. __pycache__), but never
    # ignore modified tracked files, nor untracked files inside the Theme.
    tracked_dirty = subprocess.check_output(
        ["git", "status", "--porcelain", "--untracked-files=no"], cwd=ROOT, text=True
    ).strip()
    untracked_theme = subprocess.check_output(
        ["git", "ls-files", "--others", "--exclude-standard", "--", "theme/beyourlover-v3"],
        cwd=ROOT, text=True
    ).strip()
    return not tracked_dirty and not untracked_theme


def assert_path(path: str) -> None:
    p = PurePosixPath(path)
    if (not path or path.startswith("/") or "\\" in path or p.is_absolute()
            or any(segment in ("", ".", "..") or segment in BLOCKED for segment in p.parts)
            or p.as_posix() != path):
        raise ThemeOSViolation(f"Unsafe package path: {path!r}")


def parse_version(style: str) -> str:
    match = re.search(r"^\s*\*?\s*Version:\s*([^\s]+)\s*$", style, re.M)
    if not match:
        raise ThemeOSViolation("Theme style.css is missing WordPress Version")
    return match.group(1)


def ordered_modules(manifest: dict) -> list[str]:
    modules = manifest["modules"]
    visited: set[str] = set()
    pending: set[str] = set()
    ordering: list[str] = []

    def visit(name: str) -> None:
        if name in pending:
            raise ThemeOSViolation(f"Dependency cycle involving {name}")
        if name in visited:
            return
        if name not in modules:
            raise ThemeOSViolation(f"Unknown dependency {name}")
        pending.add(name)
        for dependency in modules[name]["depends_on"]:
            visit(dependency)
        pending.remove(name)
        visited.add(name)
        ordering.append(name)

    for name in modules:
        visit(name)
    return ordering


def validate(manifest: dict | None = None, root: Path = THEME) -> dict:
    if manifest is None:
        manifest = json.loads((root / "theme-os.json").read_text(encoding="utf-8"))
    if manifest.get("schema") != 1 or manifest.get("theme") != "beyourlover-v3":
        raise ThemeOSViolation("Unexpected Theme OS schema / theme slug")
    version = manifest.get("theme_version", "")
    if not re.fullmatch(r"(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)", version):
        raise ThemeOSViolation("Theme version must be SemVer MAJOR.MINOR.PATCH")
    if manifest.get("version_policy") != "semver":
        raise ThemeOSViolation("SemVer release policy missing")
    style = (root / "style.css").read_text(encoding="utf8")
    if parse_version(style) != version:
        raise ThemeOSViolation(f"style.css Version {parse_version(style)!r} != Theme OS version {version!r}")
    if re.search(r"^\s*\*?\s*Template:\s*", style, re.M):
        raise ThemeOSViolation("BYL Theme is self-owned; no WordPress child parent Template allowed")
    modules = manifest.get("modules")
    if not isinstance(modules, dict) or not REQUIRED.issubset(modules):
        raise ThemeOSViolation("Required Theme OS modules missing")
    seen: dict[str, str] = {}
    for module_id, meta in modules.items():
        if not re.fullmatch(r"[a-z][a-z0-9-]*", module_id):
            raise ThemeOSViolation(f"Illegal module ID: {module_id!r}")
        if meta.get("kind") not in MODULE_TYPES:
            raise ThemeOSViolation(f"Invalid kind in module {module_id}")
        deps = meta.get("depends_on")
        paths = meta.get("files")
        if not isinstance(deps, list) or not isinstance(paths, list) or not paths:
            raise ThemeOSViolation(f"Incomplete dependency/file contract for {module_id}")
        if len(set(deps)) != len(deps) or module_id in deps:
            raise ThemeOSViolation(f"Duplicate/self dependency in {module_id}")
        if len(set(paths)) != len(paths):
            raise ThemeOSViolation(f"Duplicate file in module {module_id}")
        for relative in paths:
            if not isinstance(relative, str):
                raise ThemeOSViolation(f"Non-string path in {module_id}")
            assert_path(relative)
            if relative in seen:
                raise ThemeOSViolation(f"{relative} belongs to both {seen[relative]} and {module_id}")
            seen[relative] = module_id
            resolved = (root / relative)
            if not resolved.is_file() or resolved.is_symlink():
                raise ThemeOSViolation(f"Missing or symlinked registered file: {relative}")
    order = ordered_modules(manifest)
    real_files = {
        p.relative_to(root).as_posix()
        for p in root.rglob("*") if p.is_file() and not p.is_symlink()
    }
    symlinks = [p.relative_to(root).as_posix() for p in root.rglob("*") if p.is_symlink()]
    if symlinks:
        raise ThemeOSViolation(f"Symbolic links are prohibited in Theme: {symlinks}")
    missing = set(seen) - real_files
    unowned = real_files - set(seen) - {"theme-os.json"}
    if missing or unowned:
        raise ThemeOSViolation(f"Unowned/vanished Theme files: extra={sorted(unowned)} missing={sorted(missing)}")
    if set(manifest.get("business_logic_owners", {})) != {
        "theme", "andy-core", "andy-commerce", "woocommerce", "byl-erp"
    }:
        raise ThemeOSViolation("Business ownership contract is incomplete")
    # Prevent the historically-heavy dependencies from being brought back as modules.
    for name in modules:
        if "commercekit" in name or "shoptimizer" in name:
            raise ThemeOSViolation(f"Legacy vendor module not allowed: {name}")
    return {"theme": manifest["theme"], "version": version, "file_count": len(real_files),
            "module_count": len(modules), "module_order": order,
            "owner_map": seen}


def affected(manifest: dict, changes: list[str]) -> dict:
    """Expand directly changed modules to their dependent modules.

    This is an impact report, not permission to skip the full CI suite.
    """
    info = validate(manifest)
    modules = manifest["modules"]
    prefix = "theme/beyourlover-v3/"
    directly = set()
    for filepath in changes:
        if filepath.startswith(prefix):
            key = filepath[len(prefix):]
            if key == "theme-os.json":
                directly.update(modules)
            elif key in info["owner_map"]:
                directly.add(info["owner_map"][key])
            else:
                raise ThemeOSViolation(f"Changed Theme file has no owner: {filepath}")
    impacted = set(directly)
    changed = True
    while changed:
        before = set(impacted)
        impacted.update(m for m, meta in modules.items()
                        if set(meta["depends_on"]) & impacted)
        changed = before != impacted
    order = info["module_order"]
    return {
        "direct": [m for m in order if m in directly],
        "affected": [m for m in order if m in impacted],
        "protected_gate": any(modules[m]["kind"] == "protected-commerce-ui" for m in impacted),
        "full_ci_required": True,
    }


def diff_from_base(base: str) -> list[str]:
    if not re.fullmatch(r"[0-9a-zA-Z_./-]+", base) or base.startswith("-"):
        raise ThemeOSViolation("Unsafe Git base reference")
    return subprocess.check_output(
        ["git", "diff", "--name-only", f"{base}...HEAD", "--", "theme/beyourlover-v3"],
        cwd=ROOT, text=True
    ).splitlines()


def build(out_dir: Path, allow_dirty: bool = False) -> tuple[Path, Path]:
    info = validate()
    if not allow_dirty and not clean_git():
        raise ThemeOSViolation("Dirty worktree; commit before packaging an installable Theme")
    sha = git_head()
    files = sorted(set(info["owner_map"]) | {"theme-os.json"})
    if not allow_dirty:
        git_tracked = set(subprocess.check_output(
            ["git", "ls-files", "--cached", "--", "theme/beyourlover-v3"],
            cwd=ROOT, text=True
        ).splitlines())
        untracked = [
            relative for relative in files
            if f"theme/beyourlover-v3/{relative}" not in git_tracked
        ]
        if untracked:
            raise ThemeOSViolation(f"Release package includes untracked files: {untracked}")
    out_dir = out_dir.resolve()
    if out_dir == THEME or out_dir.is_relative_to(THEME):
        raise ThemeOSViolation("Release packages must be outside the theme directory")
    out_dir.mkdir(parents=True, exist_ok=True)
    filename = f"beyourlover-v3-{info['version']}-{sha[:12]}"
    dest = out_dir / (filename + ".zip")
    hashes: dict[str, str] = {}
    # Fixed timestamp, sorted allowed files and normalized permissions produce bit-for-bit reproducibility.
    with zipfile.ZipFile(dest, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
        for relative in files:
            source = THEME / relative
            data = source.read_bytes()
            entry = f"beyourlover-v3/{relative}"
            hashes[entry] = digest(data)
            info_zip = zipfile.ZipInfo(entry, date_time=ZIP_STAMP)
            info_zip.compress_type = zipfile.ZIP_DEFLATED
            info_zip.create_system = 3
            info_zip.external_attr = 0o100644 << 16
            archive.writestr(info_zip, data, compress_type=zipfile.ZIP_DEFLATED, compresslevel=9)
    zip_hash = digest(dest.read_bytes())
    receipt = {
        "schema": 1,
        "theme": "beyourlover-v3",
        "theme_version": info["version"],
        "git_sha": sha,
        "dirty": not clean_git(),
        "zip_filename": dest.name,
        "zip_sha256": zip_hash,
        "file_sha256": hashes,
        "module_count": info["module_count"],
        "owner_uat": "NOT_GRANTED",
        "production_deploy": "NOT_AUTHORIZED",
        "runtime_activated": False,
    }
    sidecar = out_dir / (filename + ".receipt.json")
    sidecar.write_text(json.dumps(receipt, sort_keys=True, indent=2) + "\n", encoding="utf8")
    return dest, sidecar


def verify(zip_path: Path, receipt_file: Path) -> dict:
    receipt = json.loads(receipt_file.read_text(encoding="utf8"))
    if receipt.get("schema") != 1 or receipt.get("zip_filename") != zip_path.name:
        raise ThemeOSViolation("Mismatched receipt identity")
    if digest(zip_path.read_bytes()) != receipt.get("zip_sha256"):
        raise ThemeOSViolation("ZIP checksum differs from expected release receipt")
    expected = receipt.get("file_sha256")
    if not isinstance(expected, dict) or not expected:
        raise ThemeOSViolation("Missing per-file SHA256 receipt")
    with zipfile.ZipFile(zip_path) as archive:
        members = archive.namelist()
        if len(members) != len(set(members)) or set(members) != set(expected):
            raise ThemeOSViolation("ZIP contains unexpected, missing or duplicate members")
        for member in members:
            if not member.startswith("beyourlover-v3/"):
                raise ThemeOSViolation("ZIP escape from Theme directory")
            assert_path(member)
            if digest(archive.read(member)) != expected[member]:
                raise ThemeOSViolation(f"Modified ZIP member {member}")
    if "beyourlover-v3/style.css" not in expected or "beyourlover-v3/theme-os.json" not in expected:
        raise ThemeOSViolation("Missing required installable Theme files")
    return {"zip_sha256": receipt["zip_sha256"], "files": len(expected),
            "version": receipt["theme_version"], "git_sha": receipt["git_sha"]}


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    actions = parser.add_subparsers(dest="command", required=True)
    actions.add_parser("validate")
    build_parser = actions.add_parser("build")
    build_parser.add_argument("--out-dir", required=True, type=Path)
    build_parser.add_argument("--allow-dirty", action="store_true",
                              help="Only for local unpublished test artifacts; receipt marks dirty.")
    impact_parser = actions.add_parser("impact")
    impact_parser.add_argument("--base", required=True, help="Git merge-base reference, e.g. origin/main")
    verify_parser = actions.add_parser("verify")
    verify_parser.add_argument("--zip", required=True, type=Path)
    verify_parser.add_argument("--receipt", required=True, type=Path)
    args = parser.parse_args()
    if args.command == "validate":
        result = validate()
    elif args.command == "impact":
        changes = diff_from_base(args.base)
        result = {"changed_theme_files": changes, **affected(
            json.loads(MANIFEST_FILE.read_text(encoding="utf8")), changes)}
    elif args.command == "build":
        archive, receipt = build(args.out_dir, args.allow_dirty)
        result = {"zip": str(archive), "receipt": str(receipt),
                  "verified": verify(archive, receipt)}
    else:
        result = verify(args.zip, args.receipt)
    print(json.dumps(result, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    try:
        main()
    except (OSError, ValueError, subprocess.CalledProcessError, zipfile.BadZipFile) as err:
        print(f"THEME_OS_FAIL: {err}", file=sys.stderr)
        sys.exit(1)
