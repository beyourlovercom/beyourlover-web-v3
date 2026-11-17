"""Synthetic exact-five WWW->Dev SKU-only Canary preflight tests."""
import copy
import unittest
from byl_a0_a2_catalog_diff import SCHEMA, SCOPE, SKU_CONTRACT
from byl_a2_www_sku_canary_plan import build_plan, ALLOWLIST

def data():
    www=[]
    dev=[]
    for x in ALLOWLIST:
        id=x["id"]
        isvar=x["type"]=="variation"
        www.append({"id":id,"type":x["type"],"sku":x["www_sku"],
                    "status":"publish","regular_price":x["regular_price"],
                    "sale_price":x["sale_price"],"parent_ref":"pillow" if isvar else "",
                    "attribute_columns":[("Color:",x["color"].title())] if isvar else []})
        dev.append({"id":id,"type":x["type"],"sku":x["dev_before"],
                    "sku_origin":"stored","status":"publish","raw_post_status":"publish",
                    "regular_price":x["regular_price"],"sale_price":x["sale_price"],
                    "parent_id":38047 if isvar else 0,
                    "parent_sku":"pillow" if isvar else "",
                    "attributes":{"pa_color":[x["color"]]} if isvar else {}})
    www.append({"id":38047,"type":"variable","sku":"pillow","status":"publish",
                "regular_price":"","sale_price":"","parent_ref":"","attribute_columns":[]})
    dev.append({"id":38047,"type":"variable","sku":"pillow",
                "sku_origin":"stored","status":"publish","raw_post_status":"publish",
                "regular_price":"","sale_price":"","parent_id":0,"parent_sku":"",
                "attributes":{"pa_color":["blue","green","grey"]}})
    for i in range(309):
        id=50000+i
        www.append({"id":id,"type":"simple","sku":"www-"+str(i),"status":"publish",
                    "regular_price":"","sale_price":"","parent_ref":"","attribute_columns":[]})
    for i in range(315):
        id=60000+i
        dev.append({"id":id,"type":"simple","sku":"dev-"+str(i),
                    "sku_origin":"stored","status":"publish","raw_post_status":"publish",
                    "regular_price":"","sale_price":"","attributes":{}})
    assert len(www)==315 and len(dev)==321
    return www,{"schema":SCHEMA,"scope":SCOPE,"sku_contract":SKU_CONTRACT,
                "site":"https://dev.beyourlover.com","products":dev,
                "counts":{"records":321,"product_variations":3}}

def alter_www(www,id,field,value):
    for x in www:
        if x["id"]==id:
            x[field]=value
            return
    raise AssertionError("missing id")

def alter_dev(dev,id,field,value):
    for x in dev["products"]:
        if x["id"]==id:
            x[field]=value
            return
    raise AssertionError("missing id")

class OwnerWWWOnlySKUPlan(unittest.TestCase):
    def test_five_exact_changes_are_read_only(self):
        www,dev=data()
        p=build_plan(www,dev)
        self.assertEqual(p["sku_authority"],"WWW")
        self.assertEqual(p["proposed_change_count"],5)
        self.assertEqual(p["gate"],"HOLD")
        self.assertFalse(p["a2_import_authorized"])
        self.assertTrue(p["require_fresh_full_dev_backup_and_restore_PASS"])
        self.assertTrue(p["require_A1_provider_and_cron_isolation_PASS"])
        self.assertEqual([x["www_canonical_stored_sku"] for x in p["proposed_sku_changes"]],
                         ["VP757A","VP757A & MF414A","pillow-blue","pillow-green","pillow-grey"])
        self.assertTrue(all(x["field_allowlist"]==["_sku"] and not x["apply_allowed"]
                            for x in p["proposed_sku_changes"]))

    def test_unexpected_dev_native_sku_blocks(self):
        w,d=data()
        alter_dev(d,35550,"sku","VP757C")
        with self.assertRaisesRegex(ValueError,"before SKU changed"):
            build_plan(w,d)

    def test_unexpected_www_native_sku_blocks(self):
        w,d=data()
        alter_www(w,36134,"sku","UNAUTHORIZED")
        with self.assertRaisesRegex(ValueError,"before SKU changed"):
            build_plan(w,d)

    def test_variation_price_mismatch_blocks(self):
        w,d=data()
        alter_dev(d,38164,"regular_price","0")
        with self.assertRaisesRegex(ValueError,"regular price mismatch"):
            build_plan(w,d)

    def test_variation_option_change_blocks(self):
        w,d=data()
        alter_dev(d,38165,"attributes",{"pa_color":["red"]})
        with self.assertRaisesRegex(ValueError,"color/attributes mismatch"):
            build_plan(w,d)

    def test_variation_parent_change_blocks(self):
        w,d=data()
        alter_dev(d,38166,"parent_id",12345)
        with self.assertRaisesRegex(ValueError,"parent identity drift"):
            build_plan(w,d)

    def test_target_sku_already_used_by_another_dev_product_blocks(self):
        w,d=data()
        alter_dev(d,60000,"sku","pillow-blue")
        with self.assertRaisesRegex(ValueError,"target SKU already assigned"):
            build_plan(w,d)

    def test_live_status_drift_blocks(self):
        w,d=data()
        alter_www(w,35550,"status","draft")
        with self.assertRaisesRegex(ValueError,"status drift"):
            build_plan(w,d)

    def test_snapshot_schema_drift_blocks(self):
        w,d=data()
        d["schema"]="byl-catalog-a0-v1"
        with self.assertRaisesRegex(ValueError,"legacy v1"):
            build_plan(w,d)

    def test_unexpected_row_count_blocks(self):
        w,d=data()
        w.pop()
        with self.assertRaisesRegex(ValueError,"snapshot sizes changed"):
            build_plan(w,d)

    def test_does_not_mutate_input_snapshots(self):
        w,d=data()
        w0,d0=copy.deepcopy(w),copy.deepcopy(d)
        build_plan(w,d)
        self.assertEqual(w,w0)
        self.assertEqual(d,d0)

if __name__=="__main__":
    unittest.main()
