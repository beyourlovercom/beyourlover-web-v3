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
        self.assertFalse(report["owner_decision"]["applied_to_any_site"])
        self.assertEqual(report["gate"],"HOLD")
        self.assertFalse(report["a0_complete_both_sites"])
        self.assertFalse(report["a2_import_authorized"])
        self.assertFalse(report["media_r2_term_id_parity_attested"])

    def test_www_own_variation_sku_is_recommended_only_not_writable(self):
        www=read([row(10,"variable","PILLOW"),
                  row(21,"variation","pillow-blue",parent="PILLOW",
                      attribute="Color",attr_value="Blue")])
        rpt=analyze(www,dev(v2_item(91,"PILLOW","variable")))
        entry=rpt["candidates_private"]["www_variations_with_own_sku"][0]
        self.assertEqual(entry["proposed_dev_sku_after_verified_variation_identity"],"pillow-blue")
        self.assertEqual(entry["owner_sku_authority"],"WWW")
        self.assertIs(entry["application_allowed"],False)
        self.assertEqual(rpt["gate"],"HOLD")

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

    def test_same_post_id_changed_sku_is_quarantined_not_auto_synced(self):
        www=read([row(35550,sku="VP757A"),row(36134,sku="VP757A & MF414A")])
        devv=dev(v2_item(35550,"VP757B"),v2_item(36134,"VP757B & MF414A"))
        rpt=analyze(www,devv)
        self.assertEqual(rpt["counts"]["potential_same_id_sku_drift"],2)
        self.assertEqual(rpt["counts"]["www_skus_without_dev_counterpart_excluding_drift"],0)
        self.assertEqual(rpt["counts"]["dev_skus_without_www_counterpart_excluding_drift"],0)
        self.assertEqual(len(rpt["candidates_private"]["possible_same_id_sku_drifts"]),2)
        self.assertEqual(rpt["owner_decision"]["sku_authority"],"WWW")
        self.assertTrue(rpt["owner_decision"]["requires_verified_product_identity_and_owner_approved_dev_write"])
        for x in rpt["candidates_private"]["possible_same_id_sku_drifts"]:
            self.assertEqual(x["proposed_dev_sku_after_verified_identity"],x["www_sku"])
            self.assertIs(x["application_allowed"],False)
        self.assertFalse(rpt["a2_import_authorized"])

    def test_unused_second_attribute_is_not_false_wildcard(self):
        www=read([row(10,"variable","P"),
                  row(21,"variation",parent="P",attribute="Color",attr_value="Red")],
                 headers=HEADERS)
        rpt=analyze(www,dev(v2_item(91,"P","variable")))
        self.assertEqual(rpt["counts"]["www_variations_missing_any_attributes"],0)

    def test_provisional_status_mismatch_is_reported_not_auto_fixed(self):
        www=read([row(10,sku="A",published="0")])
        rpt=analyze(www,dev(v2_item(12,"A")))
        self.assertEqual(rpt["counts"]["parent_status_mismatches_on_provisional_matches"],1)
        self.assertFalse(rpt["a2_import_authorized"])
        self.assertEqual(rpt["candidates_private"]["parent_status_mismatches"][0]["www_status"],"private")

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

    def test_actual_www_bom_after_blank_preamble_and_short_batches(self):
        import io
        with tempfile.TemporaryDirectory() as tmp:
            file=Path(tmp)/"www-export.csv"
            stream=io.StringIO(newline="")
            writer=csv.writer(stream)
            writer.writerow([" "])
            writer.writerow([])
            writer.writerow(HEADERS + ["Meta: custom field 1", "Meta: custom field 2"])
            # Woo exporter can add custom columns after emitting 50 row batches.
            writer.writerow([row(100,sku="RED")["ID"],"simple","RED","1","","","",
                             "","",""])  # 10 instead of 11 columns
            # Reproduce two blank lines followed by an embedded BOM on header.
            payload = stream.getvalue().replace("ID,Type", chr(0xfeff) + "ID,Type", 1)
            file.write_bytes(payload.encode("utf-8"))
            rows=read_www_csv(file)
            self.assertEqual(len(rows),1)
            self.assertEqual(rows[0]["sku"],"RED")

    def test_csv_extra_columns_rejected_even_when_prefix_valid(self):
        with tempfile.TemporaryDirectory() as tmp:
            file=Path(tmp)/"www-export.csv"
            with file.open("w",encoding="utf-8",newline="") as handle:
                writer=csv.writer(handle)
                writer.writerow(HEADERS)
                writer.writerow(list(row(9).values())+["EXTRA"])
            with self.assertRaisesRegex(ValueError,"Malformed native Woo CSV row width"):
                read_www_csv(file)

    def test_dev_snapshot_contract_cannot_be_fooled(self):
        www=read([row(1,sku="A")])
        d=dev(v2_item(2,"A"))
        d["schema"]="byl-catalog-a0-v1"
        with self.assertRaisesRegex(ValueError,"legacy v1"):
            analyze(www,d)

if __name__=="__main__":
    unittest.main()
