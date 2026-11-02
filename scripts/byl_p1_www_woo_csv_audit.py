#!/usr/bin/env python3
"""BYL P1 read-only audit of native WooCommerce WWW CSV vs trusted Dev v2 JSON.

This does NOT convert a storefront CSV into an authoritative A0 v2 snapshot.
No network access, credentials, import/write client or WordPress operations.
Never store source CSV or detailed private report in GitHub / public webroot.
"""
import argparse
import collections
import csv
import json
from pathlib import Path

from byl_a0_a2_catalog_diff import validate_snapshot

SCHEMA = "byl-p1-www-woo-csv-limited-audit-v1"
FIXTURE_DEV_IDS = frozenset({40026, 40027, 40028, 40029})
REQUIRED_HEADERS = {"ID", "Type", "SKU", "Published", "Parent", "Images", "Categories"}
STATUS_MAP = {"1": "publish", "0": "private", "-1": "draft", "2": "pending"}
ALLOWED_TYPES = {"simple", "variable", "variation", "grouped", "external"}
MAX_ROWS = 50000

def read_www_csv(path):
    p = Path(path)
    # The native WWW export observed on 2026-11-02 begins with two
    # whitespace-only CSV records, then a UTF-8 BOM *inside* the file.
    # Its 50-row batches have 135/143/147/149/150 fields against a
    # 150-column header (late-discovered plugin meta fields). Normalize
    # only absent TRAILING fields to empty for limited parent SKU audit;
    # do NOT attest custom meta, variation attributes or full R2 parity.
    with p.open("r", encoding="utf-8-sig", newline="") as handle:
        reader = csv.reader(handle)
        header = None
        for index, raw in enumerate(reader):
            if index > 5:
                raise ValueError("Native Woo CSV header missing after leading whitespace")
            if not raw or all(not cell.strip().strip(chr(0xfeff)) for cell in raw):
                continue
            header = [cell.strip(chr(0xfeff)).strip() for cell in raw]
            break
        if not header or header[0] != "ID":
            raise ValueError("Not a native Woo product export header")
        missing = REQUIRED_HEADERS - set(header)
        if missing:
            raise ValueError("Missing Woo native product CSV fields: " + ", ".join(sorted(missing)))
        if len(header) != len(set(header)):
            raise ValueError("Duplicate column headers")
        required_max = max(header.index(k) for k in REQUIRED_HEADERS)
        records = []
        ids = set()
        for i, cells in enumerate(reader, 1):
            if not cells or all(not cell.strip() for cell in cells):
                continue
            if i > MAX_ROWS:
                raise ValueError("CSV exceeds safety row limit")
            if len(cells) > len(header) or len(cells) <= required_max:
                raise ValueError("Malformed native Woo CSV row width")
            row = dict(zip(header, cells + [""] * (len(header) - len(cells))))
            value = (row.get("ID") or "").strip()
            if not value.isdecimal() or int(value) < 1:
                raise ValueError("Invalid native WWW product ID in CSV row " + str(i))
            pid = int(value)
            if pid in ids:
                raise ValueError("Duplicate native WWW product ID")
            ids.add(pid)
            kind = (row.get("Type") or "").split(",", 1)[0].strip().lower()
            if kind not in ALLOWED_TYPES:
                raise ValueError("Unrecognized Woo product type at row " + str(i))
            published = (row.get("Published") or "").strip()
            if published not in STATUS_MAP:
                raise ValueError("Unknown/unsupported published status at row " + str(i))
            records.append({
                "id": pid, "type": kind, "sku": (row.get("SKU") or "").strip(),
                "status": STATUS_MAP[published], "parent_ref": (row.get("Parent") or "").strip(),
                "images_present": bool((row.get("Images") or "").strip()),
                "categories_present": bool((row.get("Categories") or "").strip()),
                # Only actually declared attribute names count. A blank
                # unused Attribute 2 column is NOT a wildcard attribute.
                "attribute_columns": [
                    (str(row.get(k) or "").strip(),
                     str(row.get(k.replace(" name", " value(s)")) or "").strip())
                    for k in header if k.startswith("Attribute ") and k.endswith(" name")
                    if str(row.get(k) or "").strip() or
                       str(row.get(k.replace(" name", " value(s)")) or "").strip()
                ],
            })
    if not records:
        raise ValueError("Empty Woo product inventory")
    return records

def analyze(www, dev):
    validate_snapshot(dev, "https://dev.beyourlover.com")
    parents = [x for x in www if x["type"] != "variation"]
    variations = [x for x in www if x["type"] == "variation"]
    byid = {x["id"]: x for x in parents}
    skugroups = collections.defaultdict(list)
    for p in parents:
        if p["sku"].strip():
            skugroups[p["sku"].casefold()].append(p)
    www_duplicates = {s: [p["id"] for p in ps] for s, ps in skugroups.items() if len(ps)>1}
    unresolved_parents = []
    wildcard_variations = []
    for v in variations:
        ref = v["parent_ref"]
        if ref.casefold().startswith("id:") and ref[3:].strip().isdecimal():
            match = byid.get(int(ref[3:].strip()))
            candidates = [match] if match is not None else []
        else:
            candidates = skugroups.get(ref.casefold(), []) if ref else []
        if len(candidates) != 1 or candidates[0]["type"] != "variable":
            unresolved_parents.append(v["id"])
        if not v["sku"] and (
            not v["attribute_columns"] or
            any(not name or not value for name, value in v["attribute_columns"])
        ):
            wildcard_variations.append(v["id"])
    dev_parents = [x for x in dev["products"] if x["type"] != "variation"
                   and x["id"] not in FIXTURE_DEV_IDS]
    dev_sku_groups = collections.defaultdict(list)
    for x in dev_parents:
        sku = x["sku"].strip().casefold()
        if sku:
            dev_sku_groups[sku].append(x)
    matched = []
    status_mismatches = []
    collisions = []
    for sku in sorted(set(skugroups) & set(dev_sku_groups)):
        w = skugroups[sku]
        d = dev_sku_groups[sku]
        if len(w) != 1 or len(d) != 1:
            collisions.append({"sku": sku, "www_ids": [x["id"] for x in w],
                               "dev_ids": [x["id"] for x in d],
                               "reason": "DUPLICATE_SKU"})
        elif w[0]["type"] != d[0]["type"]:
            collisions.append({"sku": sku, "www_ids": [w[0]["id"]],
                               "dev_ids": [d[0]["id"]],
                               "reason": "TYPE_MISMATCH"})
        else:
            matched.append({"sku": sku, "www_id": w[0]["id"],
                            "dev_id": d[0]["id"],
                            "type": w[0]["type"]})
            if w[0]["status"] != d[0]["status"]:
                status_mismatches.append({"sku":sku,"www_id":w[0]["id"],
                    "dev_id":d[0]["id"],"www_status":w[0]["status"],
                    "dev_status":d[0]["status"]})
    # Only rows with a single SKU can be treated as missing/extra candidates.
    only_www = sorted(s for s in skugroups.keys() - dev_sku_groups.keys()
                      if len(skugroups[s]) == 1)
    only_dev = sorted(s for s in dev_sku_groups.keys() - skugroups.keys()
                      if len(dev_sku_groups[s]) == 1)
    return {
        "schema": SCHEMA,
        "source_format": "NATIVE_WOO_CSV_UNATTESTED_EXPORT_SCOPE",
        "target_format": "AUTHORIZED_DEV_A0_V2",
        "scope_caution": "Source CSV lacks verifiable full status/media/term-id/R2 coverage; only parent SKU comparisons are provisional.",
        "counts": {
            "www_csv_rows": len(www), "www_parents": len(parents),
            "www_variations": len(variations),
            "www_parent_statuses": dict(sorted(collections.Counter(x["status"] for x in parents).items())),
            "www_blank_parent_skus": sum(not x["sku"] for x in parents),
            "www_duplicate_parent_sku_groups": len(www_duplicates),
            "www_unresolved_variation_parents": len(unresolved_parents),
            "www_blank_sku_variations": sum(not x["sku"] for x in variations),
            "www_variations_missing_any_attributes": len(wildcard_variations),
            "www_parents_without_csv_images": sum(not x["images_present"] for x in parents),
            "dev_parents_excluding_four_uat_fixtures": len(dev_parents),
            "dev_variations": dev["counts"]["product_variations"],
            "parent_unique_sku_matches_provisional": len(matched),
            "parent_sku_www_only_provisional": len(only_www),
            "parent_sku_dev_only_provisional": len(only_dev),
            "parent_sku_collisions": len(collisions),
            "parent_status_mismatches_on_provisional_matches": len(status_mismatches),
            "www_variations_with_own_stored_sku": sum(bool(x["sku"]) for x in variations),
        },
        "candidates_private": {
            "parent_sku_www_only": only_www,
            "parent_sku_dev_only": only_dev,
            "parent_sku_collisions": collisions,
            "www_unresolved_variation_ids": sorted(unresolved_parents),
            "www_variation_attribute_wildcard_ids": sorted(wildcard_variations),
            "www_blank_sku_parent_ids": sorted(p["id"] for p in parents if not p["sku"]),
            "www_duplicate_parent_sku": www_duplicates,
            "parent_provisional_matches": matched,
            "parent_status_mismatches": status_mismatches,
            "www_variations_with_own_sku": [
                {"id":v["id"],"sku":v["sku"],"parent_ref":v["parent_ref"]}
                for v in variations if v["sku"]
            ],
        },
        "www_full_export_attested": False,
        "variation_identity_parity_attested": False,
        "media_r2_term_id_parity_attested": False,
        "a0_complete_both_sites": False,
        "a1_complete": False,
        "a2_import_authorized": False,
        "gate": "HOLD",
    }

def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--www-woo-csv", required=True)
    parser.add_argument("--dev-v2", required=True)
    parser.add_argument("--out", required=True)
    args = parser.parse_args()
    www = read_www_csv(args.www_woo_csv)
    dev = json.loads(Path(args.dev_v2).read_text(encoding="utf-8"))
    report = analyze(www, dev)
    dest = Path(args.out)
    dest.parent.mkdir(parents=True, exist_ok=True)
    dest.write_text(json.dumps(report, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(json.dumps({"gate": report["gate"], "counts": report["counts"],
                      "note": "LOCAL ONLY: do not publish detailed private report."},
                     ensure_ascii=False))

if __name__ == "__main__":
    main()
