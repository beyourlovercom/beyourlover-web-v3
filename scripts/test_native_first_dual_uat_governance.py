"""Fail-closed AGENTS governance contract: Native-First Development & Dual UAT V1.

This checks documentation consistency, never touches WordPress/Woo runtime.
Run with: python3 scripts/test_native_first_dual_uat_governance.py
"""
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
AGENTS = ROOT / "AGENTS.md"
SPEC = ROOT / "docs/agent/NATIVE-FIRST-DUAL-UAT-V1.md"
TESTING = ROOT / "docs/agent/TESTING.md"
ARCH = ROOT / "docs/agent/ARCHITECTURE.md"
COMMERCE = ROOT / "docs/agent/COMMERCE.md"
RELEASE = ROOT / "docs/agent/RELEASE.md"


def check(condition: bool, reason: str) -> None:
    if not condition:
        raise AssertionError(reason)


docs = {
    "root": AGENTS.read_text(encoding="utf-8"),
    "canonical": SPEC.read_text(encoding="utf-8"),
    "testing": TESTING.read_text(encoding="utf-8"),
    "architecture": ARCH.read_text(encoding="utf-8"),
    "commerce": COMMERCE.read_text(encoding="utf-8"),
    "release": RELEASE.read_text(encoding="utf-8"),
}
root = docs["root"]
canonical = docs["canonical"]
check(root.count("## Top-level mandatory standard — BYL Native-First Development & Dual UAT V1") == 1,
      "Missing/duplicated top-level AGENTS charter")
check("[canonical Native-First & Dual UAT standard](docs/agent/NATIVE-FIRST-DUAL-UAT-V1.md)" in root,
      "AGENTS must delegate details to real canonical doc")
check("## Autonomous execution inside an approved work package" in root,
      "Existing autonomy section must remain")
check(root.index("## Top-level mandatory standard") < root.index("## Autonomous execution"),
      "Native-First is top-level, not an appendix")
check("## Non-negotiable stops" in root and "Any WWW/Production release" in root,
      "Previously approved release safety gates must remain")
check("Native-first ownership and functional contract" in root,
      "Native controls must precede visual polish")
check("Functional gate before visual polish" in root,
      "Functional UAT before visual detail is mandatory")
check("Functional PASS and Visual Owner PASS" in root,
      "Both UAT gates must be explicit")
check("Cart → Shipping → Payment" in root,
      "Frozen three-step Owner journey must be top-level")
check("no additional Delivery/Review customer stage" in root,
      "No unauthorized extra stages")

for name, value in docs.items():
    check("Native-First" in value,
          f"{name} lost Native-First reference")
for name in ("architecture", "commerce", "canonical"):
    check("Cart → Shipping → Payment" in docs[name],
          f"{name} must preserve frozen three-step journey")
for name in ("architecture", "commerce", "testing", "release"):
    check("(NATIVE-FIRST-DUAL-UAT-V1.md)" in docs[name],
          f"{name} must link canonical governance")

for name in ("root", "canonical", "architecture", "commerce"):
    check("Shipping Address → Delivery Method → Payment → Review" not in docs[name],
          f"{name} still mandates deprecated four-step UI")
check("Cart 10 / Shipping 22 / Payment 12 / cross-flow 10" in root,
      "54 case coverage must be tracked accurately")
check("54 tracked checks" in canonical,
      "Canonical doc must carry 54-case inventory")
check("issues/125" in root and "issues/125" in canonical,
      "Checkout per-control tracking must be discoverable")
for state in ("UNTESTED", "SOURCE-ONLY", "ISOLATED PASS", "LIVE DEV PASS", "FAIL", "BLOCKED"):
    check(state in root and state in canonical,
          f"Must distinguish {state} from real Owner acceptance")
for required in (
    "Functional PASS AND Owner Visual PASS",
    "Native Woo", "do not invent a fourth",
    "exact-head", "separate explicit",
):
    check(required.lower() in canonical.lower(),
          f"Canonical standard missing required safeguard: {required}")
for path in (AGENTS, SPEC, TESTING, ARCH, COMMERCE, RELEASE):
    check(path.is_file() and path.stat().st_size > 250,
          f"Mandatory governance artifact missing or empty: {path}")

print("BYL_NATIVE_FIRST_DUAL_UAT_V1_GOVERNANCE_PASS: root AGENTS, 3-step architecture, 54 cases, two Owner UAT gates, native authority, existing release stops")
