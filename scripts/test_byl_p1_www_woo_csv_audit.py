"""Synthetic-only WWW native Woo CSV P1 audit tests; never access real sites."""
import csv
import tempfile
import unittest
from pathlib import Path

from byl_a0_a2_catalog_diff import SCHEMA as V2, SCOPE, SKU_CONTRACT
from byl_p1_www_woo_csv_audit import read_www_csv, analyze

HEADERS = ["ID", "Type", "SKU", "Published", "Parent", "Images", "Categories",
           "Attribute 1 name", "Attribute 1 value(s)"]

def row(id, type="simple", sku="", published="1", parent="", images="",
        categories="", attribute="", attr_value=""):
    return {"ID": str(id), "Type": type, "SKU": sku, "Published": str(published),
            "Parent": parent, "Images": images, "Categories": categories,
            "Attribute 1 name": attribute, "Attribute 1 value(s)": attr_value}

def v2_item(id, sku, type="simple"):
    return {"id": id, "sku": sku, "sku_origin": "stored", "type": type,
            "status": "publish", "raw_post_status": "publish",
            "categories": [], "attributes": {}, "parent_id": 0, "parent_sku": "",
            "image": None, "gallery": [], "regular_price": "10",
            "sale_price": ""}

def dev(*items):
    return {"schema": V2, "scope": SCOPE, "sku_contract": SKU_CONTRACT,
            "site": "https://dev.beyourlover.com", "products": list(items),
            "counts": {"records": len(items),
                       "product_variations": sum(x["type"] == "variation" for x in items)}}

def read(rows, headers=HEADERS, encoding="utf-8-sig"):
    with tempfile.TemporaryDirectory() as d:
        file = Path(d) / "www-products.csv"
        with file.open("w", encoding=encoding, newline="") as handle:
            writer = csv.DictWriter(handle, fieldnames=headers)
            writer.writeheader()
            writer.writerows(rows)
        return read_www_csv(file)

class NativeCsvP1SafetyTest(unittest.TestCase):
    def test_provisional_sku_match_never_authorizes_import(self):
        www = read([row(10,"simple","ABC"),row(20,"variable","P"),
                    row(21,"variation","",parent="P",attribute="Color",attr_value="Red")])
        report = analyze(www, dev(v2_item(91,"ABC"),v2_item(92,"P","variable"),
                                  v2_item(40026,"FIXTURE"),
                                  v2_item(93,"", "variation")))
        self.assertEqual(report["counts"]["parent_unique_sku_matches_provisional"], 2)
        self.assertEqual(report["counts"]["dev_parents_excluding_four_uat_fixtures"],2)
        self.assertEqual(report["counts"]["www_variations"],1)
        self.assertEqual(report["counts"]["www_unresolved_variation_parents"],0)
        self.assertEqual(report["gate"],"HOLD")
        self.assertFalse(report["a0_complete_both_sites"])
        self.assertFalse(report["a2_import_authorized"])
        self.assertFalse(report["media_r2_term_id_parity_attested"])

    def test_variation_missing_parent_and_wildcard_quarantined(self):
        www = read([row(10,"variable","P"),row(21,"variation",parent="id:10",attribute="Color"),
                    row(22,"variation",parent="MISSING",attribute="Color",attr_value="Blue")])
        report = analyze(www,dev(v2_item(91,"P","variable")))
        self.assertEqual(report["counts"]["www_variations_missing_any_attributes"], 1)
        self.assertEqual(report["candidates_private"]["www_unresolved_variation_ids"],[22])
        self.assertEqual(report["candidates_private"]["www_variation_attribute_wildcard_ids"],[21])

    def test_duplicate_www_parent_sku_and_type_mismatch_are_not_matches(self):
        www = read([row(10,"simple","DUP"), row(11,"simple","dup"),
                    row(20,"simple","DIFFERENT")])
        report = analyze(www,dev(v2_item(91,"DUP"),v2_item(92,"DIFFERENT","variable")))
        self.assertEqual(report["counts"]["www_duplicate_parent_sku_groups"],1)
        reasons={c["reason"] for c in report["candidates_private"]["parent_sku_collisions"]}
        self.assertEqual(reasons,{"DUPLICATE_SKU","TYPE_MISMATCH"})
        self.assertEqual(report["counts"]["parent_unique_sku_matches_provisional"],0)

    def test_native_status_encoding_preserved_without_guessing(self):
        www=read([row(10,"simple","P",published="1"),
                  row(11,"simple","Q",published="0"),
                  row(12,"simple","R",published="-1"),
                  row(13,"simple","S",published="2")])
        r=analyze(www,dev())
        self.assertEqual(r["counts"]["www_parent_statuses"],
                         {"draft":1,"pending":1,"private":1,"publish":1})

    def test_unknown_status_and_duplicate_id_fail_closed(self):
        with self.assertRaisesRegex(ValueError, "unsupported published status"):
            read([row(1,published="3")])
        with self.assertRaisesRegex(ValueError,"Duplicate native WWW product ID"):
            read([row(1),row(1)])
        with self.assertRaisesRegex(ValueError,"Invalid native WWW product ID"):
            read([row("ABC")])

    def test_non_native_or_truncated_csv_rejected(self):
        with self.assertRaisesRegex(ValueError,"Missing Woo native"):
            read([{"ID":"2","Type":"simple"}],headers=["ID","Type"])
        with self.assertRaisesRegex(ValueError,"Empty Woo product inventory"):
            read([])
        with self.assertRaisesRegex(ValueError,"Unrecognized Woo product type"):
            read([row(1,type="mystery")])

    def test_dev_snapshot_contract_cannot_be_fooled(self):
        www=read([row(1,sku="A")])
        d=dev(v2_item(2,"A"))
        d["schema"]="byl-catalog-a0-v1"
        with self.assertRaisesRegex(ValueError,"legacy v1"):
            analyze(www,d)

if __name__=="__main__":
    unittest.main()
