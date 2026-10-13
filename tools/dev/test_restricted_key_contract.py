#!/usr/bin/env python3
"""Dev-only restricted SSH command: reject other shells, SFTP and malformed SHAs.

Static tests + negative dynamic invocations. No Hostinger connectivity,
SSH credentials, staging file writes or Woo order/payment calls.
"""
from pathlib import Path
import os
import subprocess
import unittest

ROOT=Path(__file__).resolve().parents[2]
FORCED=ROOT/"tools/dev/forced_dev_cart_m72.sh"
WORKFLOW=(ROOT/".github/workflows/dev-only-cart-manual-apply.yml").read_text(encoding="utf-8")
SCRIPT=FORCED.read_text(encoding="utf-8")

class RestrictedDevKeyContract(unittest.TestCase):
    def reject(self, cmd):
        # Windows host verifies static safety; Linux CI tests the real Bash entry.
        if os.name=="nt":
            self.skipTest("dynamic forced-command rejection tested in Linux CI")
        env=os.environ.copy()
        env["SSH_ORIGINAL_COMMAND"]=cmd
        r=subprocess.run(["bash",str(FORCED)],input=b"",env=env,
                         capture_output=True,check=False,timeout=10)
        self.assertNotEqual(r.returncode,0)
        self.assertTrue(b"DENIED" in r.stderr or b"SSH_" in r.stderr)
        return r

    def test_gate_enforces_exact_one_command(self):
        self.assertIn('"${argv[0]}" == "apply-cart-m72"',SCRIPT)
        self.assertIn('"${#argv[@]}" -eq 6',SCRIPT)
        self.assertIn(r'^[a-f0-9]{40}$',SCRIPT)
        self.assertIn(r'^[a-f0-9]{64}$',SCRIPT)
        self.assertNotIn('eval ',SCRIPT)
        self.assertNotIn('bash -c ',SCRIPT)

    def test_admin_pinned_receipt_is_mandatory_before_upload(self):
        self.assertIn("approved-cart-m72-v1.txt",SCRIPT)
        self.assertIn("BYL-CART-M72-V1",SCRIPT)
        self.assertIn("ADMIN_APPROVAL_MISSING",SCRIPT)
        self.assertIn("ADMIN_APPROVAL_PERMISSIONS_FAIL",SCRIPT)
        self.assertIn("ADMIN_APPROVAL_FORMAT_FAIL",SCRIPT)
        self.assertIn("ADMIN_APPROVAL_MISMATCH",SCRIPT)
        self.assertIn('"${#approval_lines[@]}" -eq 1',SCRIPT)
        self.assertIn('"$approved_php" == "${argv[3]}"',SCRIPT)
        self.assertIn('"$approved_css" == "${argv[4]}"',SCRIPT)
        self.assertIn('"$approved_js" == "${argv[5]}"',SCRIPT)
        self.assertLess(SCRIPT.index("ADMIN_APPROVAL_MISMATCH"),
                        SCRIPT.index('ZIP="$BASE/.byl-dev-gate-cart-m7.2-'),
                        "approval must precede even creating an upload archive")

    def test_reject_scp_and_sftp_subsystem(self):
        for v in ("", "scp -t /tmp/a", "sftp", "sftp-server",
                  "bash -c whoami", "apply-cart-m72"):
            with self.subTest(v=v): self.reject(v)

    def test_reject_shell_injection(self):
        sha="a"*40
        h="b"*64
        for v in (f"apply-cart-m72 {sha}; touch /tmp/evil {h} {h} {h} {h}",
                  f"apply-cart-m72 {sha} {h} {h} {h} {h} extra",
                  f"apply-cart-m72 {sha} {h} {h} {h} ; cat /etc/passwd"):
            with self.subTest(v=v): self.reject(v)

    def test_reject_bad_sha_width(self):
        sha="a"*40
        h="b"*64
        for v in (f"apply-cart-m72 {sha[:-1]} {h} {h} {h} {h}",
                  f"apply-cart-m72 {sha} {h[:-1]} {h} {h} {h}",
                  f"apply-cart-m72 {sha.upper()} {h} {h} {h} {h}"):
            with self.subTest(v=v): self.reject(v)

    def test_restrict_upload_size_and_target(self):
        self.assertIn("MAX_ZIP_BYTES=1048576",SCRIPT)
        self.assertIn('bytes="$(',SCRIPT)
        self.assertIn("UPLOAD_SHA_GATE_FAIL",SCRIPT)
        self.assertIn("DEV_ROOT_MISSING",SCRIPT)
        self.assertIn("domains/dev.beyourlover.com",SCRIPT)
        self.assertNotIn("domains/www.beyourlover.com",SCRIPT)
        self.assertIn('[[ ! -e "$ZIP" ]]',SCRIPT)

    def test_workflow_cannot_use_generic_shell_or_scp(self):
        self.assertNotRegex(WORKFLOW,r"(?m)^\s+(?:scp|sftp) ")
        self.assertNotIn("bash -s --",WORKFLOW)
        self.assertIn('"apply-cart-m72 $SHA $ZIP_SHA $PHP_SHA $CSS_SHA $JS_SHA" < "$ARCHIVE"',WORKFLOW)
        self.assertIn("BYL_DEV_DEPLOY_ENABLED == 'true'",WORKFLOW)
        self.assertIn("environment: byl-dev-protected",WORKFLOW)
        self.assertIn("StrictHostKeyChecking=yes",WORKFLOW)
        self.assertIn("SSH_OPTS=(-p 65002",WORKFLOW)

    def test_forced_command_install_requires_admin(self):
        # Hostinger site access is provisioned via admin SSH, not this key.
        self.assertIn("/home/u247343654/.byl-dev-gate/apply_dev_cart_m72.sh",SCRIPT)
        self.assertNotIn("sudo ",SCRIPT)
        self.assertNotIn("chmod 777",SCRIPT)

if __name__=="__main__":
    unittest.main(verbosity=2)
