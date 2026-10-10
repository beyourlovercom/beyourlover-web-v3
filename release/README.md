# Release artifacts

Generate `release/release.json` from the exact Git checkout with:

```bash
python scripts/release_manifest.py
```

The manifest is deployment evidence, not source code.

## BYL Theme OS V1 (theme-only ZIP evidence)

Theme code uses a separate, deterministic package contract; do **not** confuse
the site-level release/release.json with a theme-specific installable archive.

    python3 scripts/theme_os.py validate
    python3 scripts/theme_os.py build --out-dir /tmp/byl-theme-candidate

The build emits one ZIP and one external receipt containing per-file SHA256,
exact Git HEAD, SemVer and NOT_AUTHORIZED release flags. This is **build
evidence**, not deployment authorization. Its Archive does not include any
plugin, Woo order data, database, uploads or server secrets. Runtime Theme
release approval and rollback gates are in docs/agent/THEME-OS-V1.md.
