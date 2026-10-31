#!/usr/bin/env python3
"""Pure read-only Dev Woo Store API public snapshot preflight.

Consumes already-captured public JSON fixtures, makes NO HTTP/WP/DB calls,
and NEVER authorizes a product import. Public variation SKU is not used as
a stable identity: Woo Store API may project each parent's SKU on variants.
"""
import argparse
import collections
import json
from pathlib import Path
from urllib.parse import urlsplit

SCHEMA = "byl-public-dev-preflight-v1"


def _id(item):
    n = item.get("id") if isinstance(item, dict) else None
    if type(n) is not int or n <= 0:
        raise ValueError("INVALID_PUBLIC_PRODUCT_ID")
    return n


def _load_json(path):
    return json.loads(Path(path).read_text(encoding="utf-8"))


def _attributes_key(attributes):
    if not isinstance(attributes, list) or not attributes:
        return None
    pairs = []
    for a in attributes:
        if not isinstance(a, dict):
            return None
        name, value = a.get("name"), a.get("value")
        if not isinstance(name, str) or not name.strip():
            return None
        if not isinstance(value, str) or not value.strip():
            return None  # wildcard / missing attribute: never auto-map
        pairs.append((name.strip().casefold(), value.strip().casefold()))
    if len(set(name for name, _ in pairs)) != len(pairs):
        return None
    return tuple(sorted(pairs))


def preflight(parent_pages, variation_snapshot):
    if not isinstance(parent_pages, list) or not parent_pages:
        raise ValueError("PUBLIC_PARENT_PAGES_REQUIRED")
    parents = []
    for page in parent_pages:
        if not isinstance(page, list):
            raise ValueError("PUBLIC_PARENT_PAGE_INVALID")
        parents += page
    if not all(isinstance(p, dict) for p in parents):
        raise ValueError("PUBLIC_PARENT_INVALID")
    ids = [_id(p) for p in parents]
    if len(ids) != len(set(ids)):
        raise ValueError("DUPLICATE_PUBLIC_PARENT_ID")
    by_parent = {p["id"]: p for p in parents}
    refs = {}
    combo_groups = collections.defaultdict(list)
    wildcard = []
    for parent in parents:
        if parent.get("type") not in ("simple", "variable"):
            raise ValueError("PUBLIC_PARENT_TYPE_INVALID")
        variants = parent.get("variations") or []
        if not isinstance(variants, list):
            raise ValueError("PUBLIC_VARIATIONS_INVALID")
        if parent["type"] == "simple" and variants:
            raise ValueError("SIMPLE_WITH_VARIATIONS")
        for v in variants:
            vid = _id(v)
            if vid in refs:
                raise ValueError("DUPLICATE_VARIATION_REFERENCE")
            refs[vid] = parent["id"]
            key = _attributes_key(v.get("attributes"))
            if key is None:
                wildcard.append(vid)
            else:
                combo_groups[(parent["id"], key)].append(vid)
    if not isinstance(variation_snapshot, dict) or (
        variation_snapshot.get("source") != "PUBLIC_STORE_API_ONLY"
    ):
        raise ValueError("PUBLIC_VARIATION_SNAPSHOT_INVALID")
    fetched = variation_snapshot.get("products")
    errors = variation_snapshot.get("errors")
    if not isinstance(fetched, list) or not isinstance(errors, list):
        raise ValueError("PUBLIC_VARIATION_SNAPSHOT_INVALID")
    fetched_ids = [_id(v) for v in fetched]
    if len(set(fetched_ids)) != len(fetched_ids):
        raise ValueError("DUPLICATE_FETCHED_VARIATION_ID")
    unresolved = sorted(set(refs) - set(fetched_ids))
    unexpected = sorted(set(fetched_ids) - set(refs))
    wrong_parent = sorted(
        v["id"] for v in fetched
        if v.get("type") != "variation" or v.get("parent") != refs.get(v["id"])
    )
    duplicate_combos = [
        {"parent_id": pid, "variation_ids": sorted(group)}
        for (pid, _), group in combo_groups.items() if len(group) > 1
    ]
    duplicate_combos.sort(key=lambda x: (x["parent_id"], x["variation_ids"]))
    duplicates = {vid for x in duplicate_combos for vid in x["variation_ids"]}
    verified_public_candidates = sorted(
        vid for vid in refs
        if vid not in wildcard and vid not in duplicates and
        vid not in unresolved and vid not in wrong_parent
    )
    parent_skus = [str(p.get("sku") or "").strip().casefold() for p in parents]
    counts = collections.Counter(s for s in parent_skus if s)
    sku_dupe = sorted(
        p["id"] for p in parents
        if p.get("sku") and counts[str(p["sku"]).strip().casefold()] > 1
    )
    projected_inherited_sku = sum(
        bool(by_parent.get(v.get("parent"))) and
        str(v.get("sku") or "").strip().casefold() ==
        str(by_parent[v["parent"]].get("sku") or "").strip().casefold()
        for v in fetched
    )
    missing_images = sorted(
        p["id"] for p in parents if not isinstance(p.get("images"), list)
        or not p.get("images")
    )
    image_urls = [
        img.get("src", "") for p in parents for img in (p.get("images") or [])
        if isinstance(img, dict) and isinstance(img.get("src"), str) and img["src"]
    ]
    hosts = dict(sorted(collections.Counter(
        (urlsplit(url).hostname or "").casefold() for url in image_urls
    ).items()))
    return {
        "schema": SCHEMA,
        "scope": "DEV_PUBLIC_API_ONLY_NOT_FULL_WP_CATALOG",
        "a0_full_www_dev_diff_verified": False,
        "a1_live_sandbox_all_gates_pass": False,
        "a2_import_authorized": False,
        "gate": "HOLD",
        "counts": {
            "public_parents": len(parents), "simple": sum(p["type"] == "simple" for p in parents),
            "variable": sum(p["type"] == "variable" for p in parents),
            "variation_references": len(refs), "variation_http_success": len(fetched),
            "wildcard_or_missing_attribute_variations": len(wildcard),
            "fully_specified_unique_public_candidates": len(verified_public_candidates),
            "variation_sku_equal_to_parent_projection": projected_inherited_sku,
            "blank_parent_skus": sum(not s for s in parent_skus),
            "duplicate_nonblank_parent_sku_ids": len(sku_dupe),
            "no_image_public_parents": len(missing_images),
            "image_references": len(image_urls),
            "unique_image_urls": len(set(image_urls)),
        },
        "image_hosts": hosts,
        "quarantine": {
            "blank_parent_sku_ids": sorted(p["id"] for p in parents if not str(p.get("sku") or "").strip()),
            "duplicate_parent_sku_ids": sku_dupe,
            "no_image_parent_ids": missing_images,
            "wildcard_or_missing_attribute_variation_ids": sorted(wildcard),
            "duplicate_attribute_combinations": duplicate_combos,
            "unresolved_variation_ids": unresolved,
            "unexpected_variation_ids": unexpected,
            "parent_mismatch_variation_ids": wrong_parent,
            "fetch_errors_count": len(errors),
        },
        "candidate_public_attribute_variation_ids": verified_public_candidates,
        "note": "Public parent/variation data is insufficient to prove true Woo variation SKU; full authorized A0 two-site export, A1 live safety, backup and Owner-approved SKU list are mandatory."
    }


def main():
    p = argparse.ArgumentParser(description=__doc__)
    p.add_argument("--parent-pages", nargs="+", required=True)
    p.add_argument("--variations", required=True)
    p.add_argument("--out", required=True)
    args = p.parse_args()
    result = preflight([_load_json(x) for x in args.parent_pages], _load_json(args.variations))
    Path(args.out).write_text(json.dumps(result, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(json.dumps({"gate": result["gate"], "counts": result["counts"]}, ensure_ascii=False))


if __name__ == "__main__":
    main()
