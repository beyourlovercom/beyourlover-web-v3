"""Theme OS fail-closed registry, version and reproducibility regression."""
from __future__ import annotations
import copy
import hashlib
import json
from pathlib import Path
from tempfile import TemporaryDirectory
import unittest

import theme_os


class TestThemeOS(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.manifest = json.loads(theme_os.MANIFEST_FILE.read_text(encoding="utf8"))

    def test_registry_positive(self):
        r = theme_os.validate(self.manifest)
        self.assertEqual(r["module_count"], 12)
        self.assertEqual(r["file_count"], len(r["owner_map"]) + 1)  # includes theme-os.json itself
        self.assertEqual(r["owner_map"]["page-newsletter.php"], "pages")
        self.assertEqual(r["owner_map"]["assets/css/newsletter.css"], "pages")
        self.assertEqual(r["version"], "0.1.0")
        self.assertLess(r["module_order"].index("cart"), r["module_order"].index("checkout"))
        self.assertEqual(r["owner_map"]["woocommerce/checkout/form-checkout.php"], "checkout")

    def test_duplicate_registration_rejected(self):
        m = copy.deepcopy(self.manifest)
        m["modules"]["shell"]["files"].append("functions.php")
        with self.assertRaisesRegex(theme_os.ThemeOSViolation, "belongs to both"):
            theme_os.validate(m)

    def test_cycle_rejected(self):
        m = copy.deepcopy(self.manifest)
        m["modules"]["foundation"]["depends_on"].append("checkout")
        with self.assertRaisesRegex(theme_os.ThemeOSViolation, "cycle"):
            theme_os.validate(m)

    def test_version_drift_rejected(self):
        m = copy.deepcopy(self.manifest)
        m["theme_version"] = "1.0.0"
        with self.assertRaisesRegex(theme_os.ThemeOSViolation, "style.css Version"):
            theme_os.validate(m)

    def test_traversal_rejected(self):
        m = copy.deepcopy(self.manifest)
        m["modules"]["foundation"]["files"].append("../../wp-config.php")
        with self.assertRaisesRegex(theme_os.ThemeOSViolation, "Unsafe"):
            theme_os.validate(m)

    def test_missing_asset_rejected(self):
        m = copy.deepcopy(self.manifest)
        m["modules"]["shell"]["files"].append("assets/js/not-found.js")
        with self.assertRaisesRegex(theme_os.ThemeOSViolation, "Missing or symlinked"):
            theme_os.validate(m)

    def test_unowned_asset_rejected(self):
        with TemporaryDirectory() as td:
            tmp = Path(td)
            # Manifest already owns all current original Theme files.
            import shutil
            shutil.copytree(theme_os.THEME, tmp / "theme")
            (tmp / "theme" / "unowned.css").write_text(".foo { color: red; }", encoding="utf8")
            with self.assertRaisesRegex(theme_os.ThemeOSViolation, "Unowned"):
                theme_os.validate(self.manifest, root=tmp / "theme")

    def test_vendor_theme_module_rejected(self):
        m = copy.deepcopy(self.manifest)
        m["modules"]["commercekit"] = {"kind": "presentation", "depends_on": [], "files": ["style.css"]}
        with self.assertRaises(theme_os.ThemeOSViolation):
            theme_os.validate(m)

    def test_impact_expands_dependent_modules(self):
        got = theme_os.affected(self.manifest, ["theme/beyourlover-v3/assets/css/tokens.css"])
        self.assertEqual(got["direct"], ["foundation"])
        self.assertTrue(got["protected_gate"])
        self.assertIn("checkout", got["affected"])
        got = theme_os.affected(self.manifest, ["theme/beyourlover-v3/assets/css/cart-focus.css"])
        self.assertEqual(got["direct"], ["cart"])
        self.assertIn("checkout", got["affected"])

    def test_unknown_new_theme_file_rejected(self):
        with self.assertRaisesRegex(theme_os.ThemeOSViolation, "no owner"):
            theme_os.affected(self.manifest, ["theme/beyourlover-v3/inc/new-cart-script.php"])

    def test_reproducible_and_sha_verified(self):
        with TemporaryDirectory() as a, TemporaryDirectory() as b:
            x, xr = theme_os.build(Path(a), allow_dirty=True)
            y, yr = theme_os.build(Path(b), allow_dirty=True)
            self.assertEqual(hashlib.sha256(x.read_bytes()).hexdigest(),
                             hashlib.sha256(y.read_bytes()).hexdigest())
            receipt = json.loads(xr.read_text(encoding="utf8"))
            self.assertEqual(len(receipt["file_sha256"]), len(theme_os.validate(self.manifest)["owner_map"]) + 1)
            self.assertEqual(receipt["owner_uat"], "NOT_GRANTED")
            result = theme_os.verify(x, xr)
            self.assertEqual(result["files"], len(theme_os.validate(self.manifest)["owner_map"]) + 1)
            payload = json.loads(yr.read_text(encoding="utf8"))
            payload["zip_sha256"] = "0" * 64
            yr.write_text(json.dumps(payload) + "\n", encoding="utf8")
            with self.assertRaisesRegex(theme_os.ThemeOSViolation, "checksum"):
                theme_os.verify(y, yr)


if __name__ == "__main__":
    unittest.main(verbosity=2)
