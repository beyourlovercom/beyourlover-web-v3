import unittest
from byl_a0_a2_catalog_diff import key_for, compare, load_snapshot, SCHEMA, SCOPE, SKU_CONTRACT


def p(id, sku='', kind='simple', parent_sku='', attrs=None, categories=None, image=None):
    return {'id': id, 'sku': sku, 'sku_origin': 'stored', 'type': kind, 'parent_sku': parent_sku,
            'attributes': attrs or {}, 'categories': categories or [], 'image': image, 'gallery': [],
            'status': 'publish', 'raw_post_status': 'inherit' if kind == 'variation' else 'publish',
            'regular_price': '20', 'sale_price': ''}


def snap(site, products):
    return {'site': site, 'schema': SCHEMA, 'scope': SCOPE,
            'sku_contract': SKU_CONTRACT, 'products': products,
            'counts': {'records': len(products), 'product_variations':
                       sum(p['type'] == 'variation' for p in products)}}


class AuditTests(unittest.TestCase):
    def test_sku_casefold(self):
        self.assertEqual(key_for(p(1, ' AbC ')), 'sku:abc')

    def test_variation_without_sku(self):
        self.assertEqual(key_for(p(4, kind='variation', parent_sku='PARENT', attrs={'pa_color': ['Red']})),
                         'variation:parent|pa_color=red')

    def test_unkeyed_simple_holds(self):
        r = compare(snap('https://www.beyourlover.com', [p(1)]), snap('https://dev.beyourlover.com', []))
        self.assertEqual(r['gate'], 'HOLD')

    def test_duplicated_sku_holds(self):
        r = compare(snap('https://www.beyourlover.com', [p(1,'X'), p(2,'x')]),
                    snap('https://dev.beyourlover.com', [p(3,'X')]))
        self.assertEqual(r['gate'], 'HOLD')

    def test_same_sku_ids_differ_and_media_cdn_hosts_ignored(self):
        a = p(111, 'SKU1', image={'id': 7, 'url': 'https://img.beyourlover.com/uploads/a.jpg', 'exists': True})
        b = p(999, 'SKU1', image={'id': 77, 'url': 'https://dev.beyourlover.com/uploads/a.jpg?ver=2', 'exists': True})
        r = compare(snap('https://www.beyourlover.com', [a]), snap('https://dev.beyourlover.com', [b]))
        self.assertEqual(r['gate'], 'DRY_RUN_ONLY')
        self.assertEqual(r['counts']['matched_unique_keys'], 1)
        self.assertEqual(r['counts']['changed_on_matching_keys'], 0)
        self.assertFalse(r['a2_import_authorized'])

    def test_missing_and_attr_delta(self):
        a = p(1, 'A', categories=['cat'])
        b = p(2, 'A', categories=['other'])
        r = compare(snap('https://www.beyourlover.com',[a,p(3,'B')]),snap('https://dev.beyourlover.com',[b]))
        self.assertEqual(r['counts']['missing_in_dev'], 1)
        self.assertEqual(r['matched_changed'][0]['changed_fields'], ['categories'])

    def test_actual_woo_simple_empty_attribute_array_validates(self):
        a = p(11,'SKU')
        b = p(22,'SKU')
        a['attributes'] = []
        report = compare(snap('https://www.beyourlover.com',[a]),
                         snap('https://dev.beyourlover.com',[b]))
        self.assertEqual(report['counts']['matched_unique_keys'],1)
        self.assertEqual(report['counts']['changed_on_matching_keys'],0)
        self.assertEqual(report['gate'],'DRY_RUN_ONLY')

    def test_variation_attribute_array_is_not_identity(self):
        a = p(11,'V',kind='variation',parent_sku='PARENT')
        a['attributes'] = []
        with self.assertRaisesRegex(ValueError,'Malformed Woo product/attribute'):
            compare(snap('https://www.beyourlover.com',[a]),
                    snap('https://dev.beyourlover.com',[]))

    def test_nonempty_list_attributes_still_rejected(self):
        a = p(11,'SKU')
        a['attributes']=['blue']
        with self.assertRaisesRegex(ValueError,'Malformed Woo product/attribute'):
            compare(snap('https://www.beyourlover.com',[a]),
                    snap('https://dev.beyourlover.com',[]))

    def test_v1_legacy_catalog_rejected_before_a2_diff(self):
        w = snap('https://www.beyourlover.com', [p(1, 'X')])
        d = snap('https://dev.beyourlover.com', [p(2, 'X')])
        w['schema'] = 'byl-catalog-a0-v1'
        with self.assertRaisesRegex(ValueError, 'legacy v1'):
            compare(w, d)

    def test_inherited_variation_sku_is_not_safe_even_when_parent_sku_matches(self):
        w = snap('https://www.beyourlover.com', [
            p(1, 'PARENT', kind='variable'),
            p(2, '', kind='variation', parent_sku='PARENT', attrs={'pa_color': ['Red']})
        ])
        d = snap('https://dev.beyourlover.com', [
            p(9, 'PARENT', kind='variable'),
            p(10, '', kind='variation', parent_sku='PARENT', attrs={'pa_color': ['Blue']})
        ])
        report = compare(w, d)
        self.assertEqual(report['counts']['matched_unique_keys'], 1)
        self.assertIn('variation:parent|pa_color=red', report['missing_in_dev'])
        self.assertIn('variation:parent|pa_color=blue', report['extra_in_dev'])
        self.assertFalse(report['a2_import_authorized'])

    def test_variation_provenance_must_be_explicitly_stored(self):
        w = snap('https://www.beyourlover.com', [p(1, 'PARENT')])
        d = snap('https://dev.beyourlover.com', [p(2, 'PARENT')])
        w['products'][0]['sku_origin'] = 'inherited_display'
        with self.assertRaisesRegex(ValueError, 'SKU provenance'):
            compare(w, d)

    def test_inherited_wp_post_status_is_accepted_and_count_validated(self):
        w = snap('https://www.beyourlover.com', [p(1, 'PARENT', kind='variable'), p(2, 'V', kind='variation', parent_sku='PARENT')])
        d = snap('https://dev.beyourlover.com', [p(3, 'PARENT', kind='variable'), p(4, 'V', kind='variation', parent_sku='PARENT')])
        self.assertEqual(compare(w,d)['counts']['matched_unique_keys'], 2)
        w['counts']['product_variations'] = 0
        with self.assertRaisesRegex(ValueError, 'counts inconsistent'):
            compare(w, d)

    def test_null_wildcard_variation_attribute_is_not_keyed(self):
        w = snap('https://www.beyourlover.com', [p(2, '', kind='variation', parent_sku='PARENT', attrs={'pa_color': [None]})])
        d = snap('https://dev.beyourlover.com', [])
        result = compare(w,d)
        self.assertEqual(result['gate'], 'HOLD')
        self.assertIn(2, result['unkeyed']['www_ids'])

    def test_same_sku_different_woo_type_is_identity_conflict_not_match(self):
        www = snap('https://www.beyourlover.com', [p(11, 'SKU', kind='simple')])
        dev = snap('https://dev.beyourlover.com', [p(22, 'sku', kind='variable')])
        result = compare(www, dev)
        self.assertEqual(result['gate'], 'HOLD')
        self.assertEqual(result['counts']['identity_conflicts'], 1)
        self.assertEqual(result['counts']['matched_unique_keys'], 0)
        self.assertEqual(result['identity_conflicts'][0]['reason'], 'PRODUCT_TYPE_MISMATCH')
        self.assertFalse(result['a2_import_authorized'])

    def test_stored_sku_reparented_variation_is_quarantined(self):
        www = snap('https://www.beyourlover.com', [
            p(33, 'VSKU', kind='variation', parent_sku='PARENT-A', attrs={'pa_color':['red']})
        ])
        dev = snap('https://dev.beyourlover.com', [
            p(77, 'VSKU', kind='variation', parent_sku='PARENT-B', attrs={'pa_color':['red']})
        ])
        result = compare(www, dev)
        self.assertEqual(result['gate'], 'HOLD')
        self.assertEqual(result['counts']['matched_unique_keys'], 0)
        self.assertEqual(result['identity_conflicts'][0]['reason'], 'VARIATION_PARENT_IDENTITY_MISMATCH')

    def test_stored_sku_variation_missing_parent_identity_still_holds(self):
        www = snap('https://www.beyourlover.com', [p(2,'VAR',kind='variation',parent_sku='')])
        dev = snap('https://dev.beyourlover.com', [p(3,'VAR',kind='variation',parent_sku='P')])
        result = compare(www, dev)
        self.assertEqual(result['gate'], 'HOLD')
        self.assertEqual(result['counts']['identity_conflicts'], 1)

    def test_same_stored_variation_sku_and_parent_is_eligible_to_diff_only(self):
        www = snap('https://www.beyourlover.com', [p(2,'VAR',kind='variation',parent_sku='PaReNt')])
        dev = snap('https://dev.beyourlover.com', [p(3,'VAR',kind='variation',parent_sku='parent')])
        result = compare(www, dev)
        self.assertEqual(result['gate'], 'DRY_RUN_ONLY')
        self.assertEqual(result['counts']['matched_unique_keys'], 1)
        self.assertEqual(result['counts']['identity_conflicts'], 0)
        self.assertFalse(result['a2_import_authorized'])

    def test_missing_attachment_holds(self):
        a=p(1,'A',image={'id':5,'url':'','exists':False})
        r=compare(snap('https://www.beyourlover.com',[a]), snap('https://dev.beyourlover.com',[p(2,'A')]))
        self.assertEqual(r['gate'], 'HOLD')
        self.assertEqual(len(r['invalid_media']['www']),1)


if __name__ == '__main__':
    unittest.main()
