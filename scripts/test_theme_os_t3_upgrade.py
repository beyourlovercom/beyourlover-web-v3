"""Theme OS T3 — file-system-only update/rollback rehearsal.

No WordPress runtime, active theme switch, network, DB, payment, plugins or site filesystem.
Never interpret a passing test as a live WordPress/WooCommerce upgrade UAT.
"""
from __future__ import annotations

import hashlib
import json
import shutil
import sys
import tempfile
import unittest
import zipfile
from pathlib import Path

import theme_os


def tree_hashes(root: Path) -> dict[str, str]:
    return {
        p.relative_to(root).as_posix(): hashlib.sha256(p.read_bytes()).hexdigest()
        for p in root.rglob("*") if p.is_file()
    }


class ThemeOSUpgradeRehearsal(unittest.TestCase):
    def test_main_package_declares_protected_owners(self):
        m = json.loads(theme_os.MANIFEST_FILE.read_text(encoding="utf8"))
        result = theme_os.validate(m)
        self.assertEqual(result["version"], "0.1.0")
        self.assertTrue({"foundation", "cart", "checkout", "notification"} <= set(m["modules"]))
        self.assertEqual(m["business_logic_owners"]["woocommerce"],
                         "authoritative-cart-totals-stock-checkout-payment-orders")
        self.assertEqual(m["business_logic_owners"]["andy-commerce"],
                         "B2C-promotion-shipping-gift-policy")

    def test_verified_zip_is_staged_and_rolled_back_losslessly(self):
        with tempfile.TemporaryDirectory(prefix="byl-t3-isolated-") as temporary:
            sandbox = Path(temporary)
            output = sandbox / "build"
            zip_path, receipt_path = theme_os.build(output, allow_dirty=True)
            receipt = json.loads(receipt_path.read_text(encoding="utf8"))
            verified = theme_os.verify(zip_path, receipt_path)
            self.assertEqual(verified["files"], len(receipt["file_sha256"]))
            self.assertEqual(verified["version"], "0.1.0")
            self.assertEqual(receipt["production_deploy"], "NOT_AUTHORIZED")
            self.assertEqual(receipt["owner_uat"], "NOT_GRANTED")

            # Only a synthetic legacy installation, never an actual site.
            live = sandbox / "synthetic-wp-content" / "themes" / "beyourlover-v3"
            live.parent.mkdir(parents=True)
            shutil.copytree(theme_os.THEME, live)
            with (live / "style.css").open("ab") as handle:
                handle.write(b"\n/* T3 synthetic legacy marker, temporary only */\n")
            (live / "local-legacy-note.txt").write_text("rollback verification", encoding="utf8")
            before = tree_hashes(live)

            staged_parent = sandbox / "staged"
            staged_parent.mkdir()
            with zipfile.ZipFile(zip_path) as archive:
                archive.extractall(staged_parent)
            staged = staged_parent / "beyourlover-v3"
            staged_hashes = tree_hashes(staged)
            expected = {
                path.removeprefix("beyourlover-v3/"): digest
                for path, digest in receipt["file_sha256"].items()
            }
            self.assertEqual(staged_hashes, expected)

            backup = sandbox / "rollback-exact"
            discarded_new = sandbox / "discarded-update"
            live.rename(backup)
            staged.rename(live)
            self.assertEqual(tree_hashes(live), expected)
            live.rename(discarded_new)
            backup.rename(live)
            self.assertEqual(tree_hashes(live), before)
            self.assertEqual((live / "local-legacy-note.txt").read_text(encoding="utf8"),
                             "rollback verification")
            self.assertEqual(tree_hashes(theme_os.THEME)["theme-os.json"],
                             tree_hashes(live)["theme-os.json"])

    def test_tampered_receipt_fails_before_any_install(self):
        with tempfile.TemporaryDirectory(prefix="byl-t3-corrupt-") as temporary:
            sandbox = Path(temporary)
            zip_path, receipt_path = theme_os.build(sandbox / "build", allow_dirty=True)
            target = sandbox / "synthetic-installed-theme"
            shutil.copytree(theme_os.THEME, target)
            before = tree_hashes(target)
            broken = json.loads(receipt_path.read_text(encoding="utf8"))
            broken["zip_sha256"] = "0" * 64
            invalid_receipt = sandbox / receipt_path.name
            invalid_receipt.write_text(json.dumps(broken), encoding="utf8")
            with self.assertRaisesRegex(theme_os.ThemeOSViolation, "checksum"):
                theme_os.verify(zip_path, invalid_receipt)
            self.assertEqual(tree_hashes(target), before)

    def test_staging_does_not_change_source_tree(self):
        # A deterministic package build must never write into the authoritative source Theme.
        prior = tree_hashes(theme_os.THEME)
        with tempfile.TemporaryDirectory(prefix="byl-t3-clean-") as temporary:
            a, ra = theme_os.build(Path(temporary) / "a", allow_dirty=True)
            b, rb = theme_os.build(Path(temporary) / "b", allow_dirty=True)
            self.assertEqual(a.read_bytes(), b.read_bytes())
            self.assertEqual(theme_os.verify(a, ra), theme_os.verify(b, rb))
        self.assertEqual(tree_hashes(theme_os.THEME), prior)


if __name__ == "__main__":
    unittest.main(verbosity=2)
