#!/usr/bin/env python3
"""Read-only A0/A2 catalog diff. Intentionally no WordPress/API write client."""
import argparse
from collections import defaultdict
import json
from pathlib import Path
from urllib.parse import urlsplit

SCHEMA = 'byl-catalog-a0-v2'
SCOPE = 'PRODUCT_AND_VARIATION_PUBLISH_PRIVATE_DRAFT_PENDING_FUTURE_INHERIT_TRASH_EXCLUDES_AUTO_DRAFT'
SKU_CONTRACT = 'EXPLICIT_STORED_EDIT_CONTEXT_NEVER_INHERITED_VIEW'
ALLOWED_STATUSES = frozenset(['publish', 'private', 'draft', 'pending', 'future', 'inherit', 'trash'])

def validate_snapshot(data, site):
    if not isinstance(data, dict) or data.get('schema') != SCHEMA or data.get('site') != site:
        raise ValueError('Snapshot schema/site mismatch; legacy v1 is unsafe for variation identity')
    if data.get('scope') != SCOPE or data.get('sku_contract') != SKU_CONTRACT:
        raise ValueError('Unattested or unsafe catalog export contract')
    items = data.get('products')
    if not isinstance(items, list) or not isinstance(data.get('counts'), dict):
        raise ValueError('Missing catalog rows/counts')
    ids = []
    for item in items:
        if not isinstance(item, dict) or type(item.get('id')) is not int or item['id'] <= 0:
            raise ValueError('Malformed product row')
        if item.get('sku_origin') != 'stored' or not isinstance(item.get('sku'), str):
            raise ValueError('Unsafe inherited/missing SKU provenance')
        if item.get('raw_post_status') not in ALLOWED_STATUSES:
            raise ValueError('Unexpected raw WordPress post status')
        if not isinstance(item.get('type'), str) or not isinstance(item.get('attributes'), dict):
            raise ValueError('Malformed Woo product/attribute')
        ids.append(item['id'])
    if len(set(ids)) != len(ids):
        raise ValueError('Duplicate WordPress IDs')
    if data['counts'].get('records') != len(items) or data['counts'].get('product_variations') != sum(x['type'] == 'variation' for x in items):
        raise ValueError('Snapshot counts inconsistent')
    return data



def load_snapshot(path, site):
    data = json.loads(Path(path).read_text(encoding='utf-8'))
    return validate_snapshot(data, site)


def key_for(product):
    if product.get('sku_origin') != 'stored':
        raise ValueError('Variation/product SKU provenance not verified')
    sku = str(product.get('sku') or '').strip().casefold()
    if sku:
        return 'sku:' + sku
    if product.get('type') != 'variation':
        return None
    parent = str(product.get('parent_sku') or '').strip().casefold()
    attrs = product.get('attributes') or {}
    if not parent or not isinstance(attrs, dict) or not attrs:
        return None
    normalized = [(str(k).casefold(), ','.join(sorted(str(v).strip().casefold() for v in (val if isinstance(val, list) else [val])))) for k, val in attrs.items()]
    if any(not key or not v or v == 'none' for key, v in normalized):
        return None
    return 'variation:' + parent + '|' + '|'.join(f'{k}={v}' for k, v in sorted(normalized))


def media_url(image):
    if not image:
        return None
    url = str(image.get('url') or '')
    if not url:
        return None
    return urlsplit(url).path.casefold().rstrip('/')  # no CDN host/query or unportable attachment ID


def index_catalog(items):
    mapping = defaultdict(list)
    unkeyed = []
    broken_media = []
    for item in items:
        key = key_for(item)
        if key is None:
            unkeyed.append(item['id'])
        else:
            mapping[key].append(item)
        images = [item.get('image')] + list(item.get('gallery') or [])
        for image in images:
            if image and (not image.get('exists') or not image.get('url')):
                broken_media.append({'product_id': item['id'], 'attachment_id': image.get('id')})
    duplicated = {k: [p['id'] for p in v] for k, v in mapping.items() if len(v) > 1}
    return mapping, unkeyed, duplicated, broken_media


def compare(www, dev):
    validate_snapshot(www, 'https://www.beyourlover.com')
    validate_snapshot(dev, 'https://dev.beyourlover.com')
    source, src_unkeyed, src_dupes, src_broken = index_catalog(www['products'])
    target, dev_unkeyed, dev_dupes, dev_broken = index_catalog(dev['products'])
    matched = []
    identity_conflicts = []
    for key in sorted(set(source) & set(target)):
        if len(source[key]) != 1 or len(target[key]) != 1:
            continue
        a, b = source[key][0], target[key][0]
        # SKU alone is insufficient when a product is replaced with a
        # different native Woo type. Do not classify as an ordinary delta.
        if a['type'] != b['type']:
            identity_conflicts.append({
                'key': key, 'www_id': a['id'], 'dev_id': b['id'],
                'reason': 'PRODUCT_TYPE_MISMATCH', 'www_type': a['type'],
                'dev_type': b['type'],
            })
            continue
        # Even an explicitly stored variation SKU can be moved between
        # parent products. Treat parent identity drift as a hard quarantine.
        if a['type'] == 'variation' and (
            str(a.get('parent_sku') or '').strip().casefold()
            != str(b.get('parent_sku') or '').strip().casefold()
            or not str(a.get('parent_sku') or '').strip()
            or not str(b.get('parent_sku') or '').strip()
        ):
            identity_conflicts.append({
                'key': key, 'www_id': a['id'], 'dev_id': b['id'],
                'reason': 'VARIATION_PARENT_IDENTITY_MISMATCH',
            })
            continue
        diffs = []
        for field in ['type', 'status', 'categories', 'attributes', 'regular_price', 'sale_price']:
            if a.get(field) != b.get(field):
                diffs.append(field)
        if media_url(a.get('image')) != media_url(b.get('image')):
            diffs.append('image')
        if sorted(media_url(x) for x in (a.get('gallery') or []) if media_url(x)) != sorted(media_url(x) for x in (b.get('gallery') or []) if media_url(x)):
            diffs.append('gallery')
        matched.append({'key': key, 'www_id': a['id'], 'dev_id': b['id'], 'changed_fields': diffs})
    missing = sorted(k for k in source.keys() - target.keys() if k not in src_dupes)
    extra = sorted(k for k in target.keys() - source.keys() if k not in dev_dupes)
    blockers = bool(src_unkeyed or dev_unkeyed or src_dupes or dev_dupes or src_broken or dev_broken or identity_conflicts)
    return {
        'schema': 'byl-catalog-diff-a0-v2',
        'sku_contract': SKU_CONTRACT,
        'full_snapshot_contract_v2': True,
        'source': www['site'], 'target': dev['site'],
        'counts': {'www': len(www['products']), 'dev': len(dev['products']),
                   'matched_unique_keys': len(matched), 'identity_conflicts': len(identity_conflicts), 'missing_in_dev': len(missing),
                   'extra_in_dev': len(extra), 'changed_on_matching_keys': sum(bool(x['changed_fields']) for x in matched)},
        'missing_in_dev': missing, 'extra_in_dev': extra,
        'matched_changed': [x for x in matched if x['changed_fields']],
        'identity_conflicts': identity_conflicts,
        'unkeyed': {'www_ids': src_unkeyed, 'dev_ids': dev_unkeyed},
        'duplicate_keys': {'www': src_dupes, 'dev': dev_dupes},
        'invalid_media': {'www': src_broken, 'dev': dev_broken},
        'a2_import_authorized': False,
        'gate': 'HOLD' if blockers else 'DRY_RUN_ONLY',
        'note': 'Reports only; A1 live safety, backup, owner scope and ID mapping must be checked separately.'
    }


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--www', required=True)
    parser.add_argument('--dev', required=True)
    parser.add_argument('--out', required=True)
    args = parser.parse_args()
    www = load_snapshot(args.www, 'https://www.beyourlover.com')
    dev = load_snapshot(args.dev, 'https://dev.beyourlover.com')
    report = compare(www, dev)
    Path(args.out).write_text(json.dumps(report, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
    print(json.dumps({'gate': report['gate'], 'counts': report['counts'], 'report': args.out}, ensure_ascii=False))


if __name__ == '__main__':
    main()
