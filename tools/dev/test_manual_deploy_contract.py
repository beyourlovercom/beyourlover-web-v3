#!/usr/bin/env python3
"""Offline safety contract for disabled manual Dev-only GitHub deployment."""
from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[2]
WORKFLOW = (ROOT / ".github/workflows/dev-only-cart-manual-apply.yml").read_text(encoding="utf-8")
APPLY = (ROOT / "tools/dev/apply_dev_cart_m72.sh").read_text(encoding="utf-8")


class ManualApplyContract(unittest.TestCase):
    def test_manual_dispatch_only(self):
        self.assertIn("workflow_dispatch:", WORKFLOW)
        self.assertNotIn("  pull_request:", WORKFLOW)
        self.assertNotIn("  push:", WORKFLOW)

    def test_explicit_double_gate(self):
        self.assertIn("vars.BYL_DEV_DEPLOY_ENABLED == 'true'", WORKFLOW)
        self.assertIn("inputs.mode == 'apply'", WORKFLOW)
        self.assertIn("inputs.owner_confirmation == 'APPROVED-DEV-ONLY-3-FILES'", WORKFLOW)
        self.assertIn("environment: byl-dev-protected", WORKFLOW)

    def test_dedicated_secrets_and_strict_known_hosts(self):
        for key in ("BYL_DEV_SSH_HOST","BYL_DEV_SSH_USER",
                    "BYL_DEV_SSH_PRIVATE_KEY","BYL_DEV_SSH_KNOWN_HOSTS"):
            self.assertIn("secrets." + key, WORKFLOW)
        self.assertIn("StrictHostKeyChecking=yes", WORKFLOW)
        self.assertIn("UserKnownHostsFile=", WORKFLOW)

    def test_no_arbitrary_pr_source(self):
        self.assertIn('pulls/95', WORKFLOW)
        self.assertIn('test "$PR_HEAD" = "$EXACT_SHA"', WORKFLOW)
        self.assertIn('test "$PR_BASE" = main', WORKFLOW)
        self.assertIn('NO_EXACT_SHA_GREEN_FOUNDATION_CI', WORKFLOW)
        self.assertIn("ref: ${{ needs.plan.outputs.approved_sha }}", WORKFLOW)

    def test_no_www_or_woo_writes(self):
        self.assertIn('BASE="/home/u247343654/domains/dev.beyourlover.com"', APPLY)
        self.assertIn('WP="$BASE/public_html"', APPLY)
        self.assertIn('https://dev.beyourlover.com', APPLY)
        self.assertIn('"staging"', APPLY)
        self.assertNotIn('www.beyourlover.com', APPLY)
        for banned in ("wp db ", "wc_order", "place_order", "wp plugin install",
                       "systemctl", "reboot", "wp config set", "wp option update"):
            self.assertNotIn(banned, APPLY)

    def test_remote_sha_lock_and_rollback(self):
        for name in ("inc/cart-drawer.php", "assets/css/cart-drawer.css",
                     "assets/js/cart-drawer.js"):
            self.assertIn(name, APPLY)
        for token in ("ZIP_DIGEST_GATE_FAIL", "ZIP_ALLOWLIST_GATE_FAIL",
                      "LIVE_OLD_SHA_GATE_FAIL", "NEW_FILE_SHA_GATE_FAIL",
                      "BACKUP_VERIFICATION_FAILED", "CONCURRENT_CHANGE_GATE_FAIL",
                      "POST_DEPLOY_SHA_FAIL", "AUTOMATIC_ROLLBACK_ATTEMPTED",
                      'trap clean_up EXIT'):
            self.assertIn(token, APPLY)


if __name__ == "__main__":
    unittest.main(verbosity=2)
