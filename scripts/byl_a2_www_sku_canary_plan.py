#!/usr/bin/env python3
"""Source-bound read-only SKU-only Dev Canary preflight, NEVER an importer.

The fixed 2026-11-02 Owner decision is WWW stored SKU authority, limited
to independently validated product/variation identity and exact before
values. This tool cannot connect to, mutate, or authorize either site.
"""
import argparse
import hashlib
import json
from pathlib import Path
from byl_a0_a2_catalog_diff import load_snapshot
from byl_p1_www_woo_csv_audit import read_www_csv

WWW_SHA256 = "39aa0efde3ed5ffe7151c71292234e052f2933016c9f014fc5f10156ee66664b"
DEV_SHA256 = "7a6b337505bedbefe5678b891befa92870c1c128b442c174e92fb22013256da0"

# Real read-back-approved candidate identities. A changed site snapshot
# or unrelated SKU state MUST stop rather than broadening the allowlist.
ALLOWLIST = (
    {"id":35550, "type":"simple", "status":"publish", "www_sku":"VP757A",
     "dev_before":"VP757B", "regular_price":"129", "sale_price":"89"},
    {"id":36134, "type":"simple", "status":"publish", "www_sku":"VP757A & MF414A",
     "dev_before":"VP757B & MF414A", "regular_price":"185", "sale_price":"148"},
    {"id":38164, "type":"variation", "status":"publish", "www_sku":"pillow-blue",
     "dev_before":"", "regular_price":"59", "sale_price":"",
     "parent_id":38047, "parent_sku":"pillow", "color":"blue"},
    {"id":38165, "type":"variation", "status":"publish", "www_sku":"pillow-green",
     "dev_before":"", "regular_price":"59", "sale_price":"",
     "parent_id":38047, "parent_sku":"pillow", "color":"green"},
    {"id":38166, "type":"variation", "status":"publish", "www_sku":"pillow-grey",
     "dev_before":"", "regular_price":"59", "sale_price":"",
     "parent_id":38047, "parent_sku":"pillow", "color":"grey"},
)

def _check(condition, message):
    if not condition:
        raise ValueError("A2_SKU_PLAN_HOLD: " + message)

def build_plan(www, dev):
    _check(dev.get("site") == "https://dev.beyourlover.com", "Dev site mismatch")
    _check(dev.get("sku_contract") == "EXPLICIT_STORED_EDIT_CONTEXT_NEVER_INHERITED_VIEW",
           "Unsafe variation stored SKU contract")
    wi = {x["id"]:x for x in www}
    di = {x["id"]:x for x in dev["products"]}
    _check(len(wi)==len(www) and len(di)==len(dev["products"]), "Duplicate native IDs")
    _check(len(www)==315 and len(dev["products"])==321,
           "Historical snapshot sizes changed: re-approve new source evidence")
    wp = wi.get(38047)
    dp = di.get(38047)
    _check(wp is not None and dp is not None, "Pillow parent missing")
    _check(wp["type"]==dp["type"]=="variable", "Pillow parent type mismatch")
    _check(wp["sku"].strip().casefold()==dp["sku"].strip().casefold()=="pillow",
           "Pillow parent stored SKU mismatch")
    _check(wp["status"]==dp["raw_post_status"]=="publish",
           "Pillow parent status mismatch")
    used = {}
    for item in dev["products"]:
        sku=str(item.get("sku") or "").strip().casefold()
        if sku:
            used.setdefault(sku, []).append(item["id"])
    planned=[]
    for item in ALLOWLIST:
        i=item["id"]
        a,b=wi.get(i),di.get(i)
        _check(a is not None and b is not None, str(i)+": missing source or target ID")
        _check(a["type"]==b["type"]==item["type"], str(i)+": Woo product type drift")
        _check(a["status"]==b["raw_post_status"]==item["status"],
               str(i)+": status drift")
        _check(a["sku"]==item["www_sku"] and b["sku"]==item["dev_before"],
               str(i)+": before SKU changed; no overwrite")
        _check(str(a.get("regular_price",""))==str(b.get("regular_price",""))
               ==item["regular_price"], str(i)+": regular price mismatch")
        _check(str(a.get("sale_price",""))==str(b.get("sale_price",""))
               ==item["sale_price"], str(i)+": sale price mismatch")
        _check(b.get("sku_origin")=="stored", str(i)+": SKU source not stored")
        target=item["www_sku"].strip().casefold()
        _check(used.get(target,[]) in ([],[i]),
               str(i)+": target SKU already assigned in Dev")
        if item["type"]=="variation":
            _check(a["parent_ref"].strip().casefold()=="pillow",
                   str(i)+": WWW parent reference drift")
            _check(b.get("parent_id")==item["parent_id"] and
                   b.get("parent_sku","").strip().casefold()==item["parent_sku"],
                   str(i)+": Dev parent identity drift")
            wa=[(name.rstrip(": ").strip().casefold(),val.strip().casefold())
                for name,val in a.get("attribute_columns",[]) if name or val]
            _check(wa==[("color",item["color"])], str(i)+": WWW color mismatch")
            attrs=b.get("attributes") or {}
            _check(attrs=={"pa_color":[item["color"]]},
                   str(i)+": Dev native color/attributes mismatch")
        planned.append({
            "www_id":i, "dev_id":i, "type":item["type"],
            "field_allowlist":["_sku"], "dev_current_stored_sku":b["sku"],
            "www_canonical_stored_sku":a["sku"],
            "status_must_remain":item["status"],
            "parent_identity": {"dev_parent_id":item["parent_id"],
                                "dev_parent_sku":item["parent_sku"],
                                "color":item["color"]} if item["type"]=="variation" else None,
            "identity_evidence":"WWW native CSV + Dev v2 type, status, price, and parent/option identity",
            "readback_required":True, "apply_allowed":False,
        })
    _check(len(planned)==5, "Unexpected number of proposed SKU changes")
    return {
        "schema":"byl-a2-owner-www-sku-canary-plan-v1",
        "sku_authority":"WWW", "www_source_sha256":WWW_SHA256,
        "dev_source_sha256":DEV_SHA256,
        "proposed_sku_changes":planned,
        "proposed_change_count":len(planned),
        "no_non_sku_fields_authorized":True,
        "no_auto_create_delete_publish":True,
        "require_current_live_preimage_and_unique_sku_revalidation":True,
        "require_owner_approved_exact_write_and_rollback":True,
        "require_A1_provider_and_cron_isolation_PASS":True,
        "require_fresh_full_dev_backup_and_restore_PASS":True,
        "a2_import_authorized":False,
        "gate":"HOLD",
        "note":"READ-ONLY PLAN. Not an importer or approval. A1 and backup gates remain open.",
    }

def main():
    parser=argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--www-csv",required=True)
    parser.add_argument("--dev-v2",required=True)
    parser.add_argument("--out",required=True)
    a=parser.parse_args()
    if hashlib.sha256(Path(a.www_csv).read_bytes()).hexdigest()!=WWW_SHA256:
        raise SystemExit("A2_SKU_PLAN_HOLD: WWW source SHA-256 mismatch")
    if hashlib.sha256(Path(a.dev_v2).read_bytes()).hexdigest()!=DEV_SHA256:
        raise SystemExit("A2_SKU_PLAN_HOLD: Dev source SHA-256 mismatch")
    data=build_plan(read_www_csv(a.www_csv),
                    load_snapshot(a.dev_v2,"https://dev.beyourlover.com"))
    out=Path(a.out)
    if not out.parent.is_dir():
        raise SystemExit("A2_SKU_PLAN_HOLD: output directory must already exist")
    out.write_text(json.dumps(data,ensure_ascii=False,indent=2)+"\n",encoding="utf-8")
    print(json.dumps({"gate":data["gate"],
                      "proposed_sku_changes":len(data["proposed_sku_changes"]),
                      "report_sha256":hashlib.sha256(out.read_bytes()).hexdigest(),
                      "a2_import_authorized":False},ensure_ascii=False))

if __name__=="__main__":
    main()
