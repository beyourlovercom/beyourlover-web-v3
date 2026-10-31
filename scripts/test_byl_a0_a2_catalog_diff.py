import unittest
from byl_a0_a2_catalog_diff import key_for, compare


def p(id, sku='', kind='simple', parent_sku='', attrs=None, categories=None, image=None):
    return {'id': id, 'sku': sku, 'type': kind, 'parent_sku': parent_sku,
            'attributes': attrs or {}, 'categories': categories or [], 'image': image, 'gallery': [],
            'status': 'publish', 'regular_price': '20', 'sale_price': ''}


def snap(site, products):
    return {'site': site, 'schema': 'byl-catalog-a0-v1', 'products': products}


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

    def test_missing_attachment_holds(self):
        a=p(1,'A',image={'id':5,'url':'','exists':False})
        r=compare(snap('https://www.beyourlover.com',[a]), snap('https://dev.beyourlover.com',[p(2,'A')]))
        self.assertEqual(r['gate'], 'HOLD')
        self.assertEqual(len(r['invalid_media']['www']),1)


if __name__ == '__main__':
    unittest.main()
