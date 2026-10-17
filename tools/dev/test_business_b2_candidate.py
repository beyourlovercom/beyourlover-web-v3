#!/usr/bin/env python3
"""Business B2 offline ZIP contract. No server or WordPress runtime involved."""
import hashlib
import json
import tempfile
import unittest
import zipfile
from pathlib import Path
from unittest.mock import patch
from business_b2_candidate import ALLOWLIST, ZIP_NAME, RECEIPT_NAME, build, verify

class DevPackageContract(unittest.TestCase):
    def test_build_verify_and_fail_closed(self):
        with tempfile.TemporaryDirectory() as tmp:
            source, out = Path(tmp)/"repo", Path(tmp)/"out"
            theme = source/"theme/beyourlover-v3"
            for filename in ALLOWLIST:
                path = theme/filename
                path.parent.mkdir(parents=True, exist_ok=True)
                path.write_text("dev.beyourlover.com\n" if filename=="page-business.php" else "Safe preview", encoding="utf-8")
            sha = "a"*40
            with patch("business_b2_candidate.git_sha", return_value=sha):
                with self.assertRaisesRegex(ValueError, "Exact SHA mismatch"):
                    build(source, "b"*40, out)
                build(source, sha, out)
            verify(out)
            with zipfile.ZipFile(out/ZIP_NAME) as archive:
                self.assertEqual(archive.namelist(), list(ALLOWLIST))
            receipt = json.loads((out/RECEIPT_NAME).read_text())
            self.assertEqual(receipt["source_sha"], sha)
            with (out/ZIP_NAME).open("ab") as archive_file:
                archive_file.write(b"tamper")
            with self.assertRaisesRegex(ValueError, "checksum mismatch"):
                verify(out)

if __name__ == "__main__":
    unittest.main()
