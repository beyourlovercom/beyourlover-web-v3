"""Synthetic-only tests for public API preflight; no live WordPress access."""
import copy
import unittest

from byl_a0_a2_public_dev_preflight import preflight


def fixture():
    parents = [
        {
            "id": 10, "type": "simple", "sku": "", "images": [], "variations": []
        },
        {
            "id": 20, "type": "variable", "sku": "PARENT", "images": [
                {"src": "https://img.beyourlover.com/p.jpg"}
            ], "variations": [
                {"id": 21, "attributes": [{"name": "Color", "value": "Red"}]},
                {"id": 22, "attributes": [{"name": "Color", "value": None}]},
                {"id": 23, "attributes": [{"name": "Color", "value": "Blue"}]},
                {"id": 24, "attributes": [{"name": "Color", "value": "Red"}]},
            ]
        },
    ]
    variants = {
        "source": "PUBLIC_STORE_API_ONLY", "errors": [], "products": [
            {"id": x, "parent": 20, "type": "variation", "sku": "PARENT"}
            for x in [21, 22, 23, 24]
        ]
    }
    return [parents], variants


class PublicDevPreflightTest(unittest.TestCase):
    def test_realistic_quarantine_does_not_authorize_migration(self):
        pages, variants = fixture()
        result = preflight(pages, variants)
        self.assertEqual(result["gate"], "HOLD")
        self.assertFalse(result["a2_import_authorized"])
        self.assertFalse(result["a0_full_www_dev_diff_verified"])
        self.assertFalse(result["a1_live_sandbox_all_gates_pass"])
        self.assertEqual(result["counts"]["variation_references"], 4)
        self.assertEqual(result["counts"]["variation_http_success"], 4)
        self.assertEqual(result["counts"]["variation_sku_equal_to_parent_projection"], 4)
        self.assertEqual(result["counts"]["wildcard_or_missing_attribute_variations"], 1)
        self.assertEqual(result["quarantine"]["blank_parent_sku_ids"], [10])
        self.assertEqual(result["quarantine"]["no_image_parent_ids"], [10])
        self.assertEqual(result["quarantine"]["wildcard_or_missing_attribute_variation_ids"], [22])
        self.assertEqual(result["quarantine"]["duplicate_attribute_combinations"],
                         [{"parent_id": 20, "variation_ids": [21, 24]}])
        self.assertEqual(result["candidate_public_attribute_variation_ids"], [23])

    def test_duplicate_wildcards_reported_but_never_autorouted(self):
        pages, variants = fixture()
        pages[0][1]["variations"].append(
            {"id": 25, "attributes": [{"name": "Color", "value": None}]}
        )
        variants["products"].append(
            {"id": 25, "type": "variation", "parent": 20, "sku": "PARENT"}
        )
        result = preflight(pages, variants)
        self.assertEqual(result["quarantine"]["duplicate_wildcard_combinations"],
                         [{"parent_id": 20, "variation_ids": [22, 25]}])
        self.assertFalse(result["a2_import_authorized"])

    def test_missing_fetched_variation_is_not_candidate(self):
        pages, variants = fixture()
        variants["products"] = variants["products"][:-1]
        report = preflight(pages, variants)
        self.assertEqual(report["quarantine"]["unresolved_variation_ids"], [24])
        self.assertEqual(report["gate"], "HOLD")

    def test_wrong_parent_and_unexpected_variant_are_flagged(self):
        pages, variants = fixture()
        variants["products"][0]["parent"] = 999
        variants["products"].append({"id": 99, "type": "variation", "parent": 20, "sku": "x"})
        report = preflight(pages, variants)
        self.assertEqual(report["quarantine"]["parent_mismatch_variation_ids"], [21])
        self.assertEqual(report["quarantine"]["unexpected_variation_ids"], [99])
        self.assertNotIn(21, report["candidate_public_attribute_variation_ids"])

    def test_duplicate_public_parent_or_variation_reference_fails_closed(self):
        pages, variants = fixture()
        duplicated = copy.deepcopy(pages)
        duplicated[0].append(copy.deepcopy(duplicated[0][0]))
        with self.assertRaisesRegex(ValueError, "DUPLICATE_PUBLIC_PARENT_ID"):
            preflight(duplicated, variants)
        duplicate_ref = copy.deepcopy(pages)
        duplicate_ref[0][1]["variations"].append({"id": 21, "attributes": []})
        with self.assertRaisesRegex(ValueError, "DUPLICATE_VARIATION_REFERENCE"):
            preflight(duplicate_ref, variants)

    def test_refuses_invalid_snapshot_or_simple_variations(self):
        pages, variants = fixture()
        with self.assertRaisesRegex(ValueError, "PUBLIC_VARIATION_SNAPSHOT_INVALID"):
            preflight(pages, {"source": "OTHER", "errors": [], "products": []})
        bad = copy.deepcopy(pages)
        bad[0][0]["variations"] = [{"id": 80, "attributes": []}]
        with self.assertRaisesRegex(ValueError, "SIMPLE_WITH_VARIATIONS"):
            preflight(bad, variants)

    def test_empty_public_inventory_and_duplicate_fetched_id_are_rejected(self):
        pages, variants = fixture()
        with self.assertRaisesRegex(ValueError, "PUBLIC_PARENT_PAGES_REQUIRED"):
            preflight([], variants)
        variants["products"].append(copy.deepcopy(variants["products"][0]))
        with self.assertRaisesRegex(ValueError, "DUPLICATE_FETCHED_VARIATION_ID"):
            preflight(pages, variants)


if __name__ == "__main__":
    unittest.main()
