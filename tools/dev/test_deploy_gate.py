#!/usr/bin/env python3
"""Offline fail-closed tests: no Hostinger, Woo, payment, network or credentials."""
from __future__ import annotations

import importlib.util
import json
from pathlib import Path
import shutil
import tempfile
import unittest
from unittest.mock import patch
import zipfile


FILE = Path(__file__).with_name("deploy_gate.py")
spec = importlib.util.spec_from_file_location("byl_dev_gate", FILE)
assert spec is not None and spec.loader is not None
gate = importlib.util.module_from_spec(spec)
spec.loader.exec_module(gate)
HEAD = "a" * 40


class DeployGateTests(unittest.TestCase):
    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory()
        self.addCleanup(self.tmp.cleanup)
        self.root = Path(self.tmp.name)
        self.repo = self.root / "src"
        theme = self.repo / "theme" / "beyourlover-v3"
        for i, name in enumerate(gate.ALLOWLIST):
            p = theme / name
            p.parent.mkdir(parents=True, exist_ok=True)
            p.write_bytes(("sample--" + str(i) + "\n").encode())
        self.head_patch = patch.object(gate, "exact_git_head", return_value=HEAD)
        self.head_patch.start()
        self.addCleanup(self.head_patch.stop)

    def build(self, name="out"):
        return gate.build_package(self.repo, HEAD, self.root / name)

    def test_deterministic_two_independent_outputs(self):
        z1, r1 = self.build("first")
        z2, r2 = self.build("second")
        self.assertEqual(z1.read_bytes(), z2.read_bytes())
        self.assertEqual(r1.read_bytes(), r2.read_bytes())
        self.assertEqual(gate.verify_package(z1, r1)["exact_git_commit"], HEAD)

    def test_allowlist_is_exact_three(self):
        z, r = self.build()
        with zipfile.ZipFile(z) as f:
            self.assertEqual(set(f.namelist()),
                {gate.THEME_PREFIX + p for p in gate.ALLOWLIST})
        receipt = json.loads(r.read_text())
        self.assertEqual(set(receipt["allowlisted_files"]), set(gate.ALLOWLIST))
        self.assertFalse(receipt["remote_apply_authorized"])
        self.assertEqual(receipt["ci_result"], "NOT_ATTESTED_BY_OFFLINE_BUILDER")

    def test_refuse_wrong_commit(self):
        with self.assertRaisesRegex(gate.DeploymentGateError, "SHA"):
            gate.build_package(self.repo, "b" * 40, self.root / "no")

    def test_refuse_dirty_checkout(self):
        with patch.object(gate, "exact_git_head", side_effect=gate.DeploymentGateError("dirty")):
            with self.assertRaisesRegex(gate.DeploymentGateError, "dirty"):
                gate.build_package(self.repo, HEAD, self.root / "dirty")

    def test_refuse_missing_file(self):
        (self.repo / "theme/beyourlover-v3/inc/cart-drawer.php").unlink()
        with self.assertRaisesRegex(gate.DeploymentGateError, "Missing"):
            self.build()

    def test_refuse_symlink_source(self):
        p = self.repo / "theme/beyourlover-v3/inc/cart-drawer.php"
        p.unlink()
        p.symlink_to(self.repo / "theme/beyourlover-v3/assets/js/cart-drawer.js")
        with self.assertRaisesRegex(gate.DeploymentGateError, "symlinked"):
            self.build()

    def test_refuse_existing_output(self):
        self.build("out")
        with self.assertRaisesRegex(gate.DeploymentGateError, "Refuse"):
            self.build("out")

    def test_reject_extra_archive_entry(self):
        z, r = self.build()
        with zipfile.ZipFile(z, "a") as f:
            f.writestr("wp-content/themes/beyourlover-v3/../../wp-config.php", "bad")
        doc = json.loads(r.read_text())
        doc["zip_sha256"] = gate.sha256(z.read_bytes())
        r.write_text(json.dumps(doc))
        with self.assertRaisesRegex(gate.DeploymentGateError, "Extra"):
            gate.verify_package(z, r)

    def test_reject_corrupted_archive_digest(self):
        z, r = self.build()
        data = bytearray(z.read_bytes())
        data[40] ^= 0x01
        z.write_bytes(data)
        with self.assertRaisesRegex(gate.DeploymentGateError, "Receipt mismatch"):
            gate.verify_package(z, r)

    def test_reject_changed_old_dev_baseline(self):
        z, r = self.build()
        doc = json.loads(r.read_text())
        doc["allowlisted_files"]["inc/cart-drawer.php"]["expected_dev_old_sha256"] = "0" * 64
        r.write_text(json.dumps(doc))
        with self.assertRaisesRegex(gate.DeploymentGateError, "Dev-baseline"):
            gate.verify_package(z, r)

    def test_reject_untrusted_remote_authorization_claim(self):
        z, r = self.build()
        doc = json.loads(r.read_text())
        doc["remote_apply_authorized"] = True
        r.write_text(json.dumps(doc))
        with self.assertRaisesRegex(gate.DeploymentGateError, "untrusted"):
            gate.verify_package(z, r)

    def test_reject_wrong_destination(self):
        with self.assertRaises(gate.DeploymentGateError):
            gate.entry_path("inc/checkout.php")
        with self.assertRaises(gate.DeploymentGateError):
            gate.entry_path("../wp-config.php")
        with self.assertRaises(gate.DeploymentGateError):
            gate.entry_path("assets\\js\\cart-drawer.js")

    def test_reject_unexpected_target(self):
        z, r = self.build()
        doc = json.loads(r.read_text())
        doc["target"] = "https://www.beyourlover.com"
        r.write_text(json.dumps(doc))
        with self.assertRaisesRegex(gate.DeploymentGateError, "Receipt mismatch"):
            gate.verify_package(z, r)


if __name__ == "__main__":
    unittest.main(verbosity=2)
