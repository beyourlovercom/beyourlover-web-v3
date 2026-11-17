"""Offline fail-closed tests for source-only A3 post-41548 Canary planning."""
import unittest
import xml.etree.ElementTree as ET

from byl_a3_c2_post_41548_source_preflight import (
    IMAGE_BLOCK, blocks_from_content, cdn, extract_manifest
)

WP = "http://wordpress.org/export/1.2/"
CONTENT = "http://purl.org/rss/1.0/modules/content/"
IMAGE_IDS = (41550, 41554, 41551, 41553, 41552)


def child(parent, tag, value):
    ET.SubElement(parent, tag).text = str(value)


def fixture(*, slug="sex-during-pregnancy", images=IMAGE_IDS,
            missing_media=None, category_override=None, featured_id="41549"):
    root = ET.Element("rss")
    channel = ET.SubElement(root, "channel")
    post = ET.SubElement(channel, "item")
    child(post, f"{{{WP}}}post_id", "41548")
    child(post, f"{{{WP}}}post_type", "post")
    child(post, f"{{{WP}}}post_name", slug)
    child(post, f"{{{WP}}}status", "publish")
    child(post, f"{{{WP}}}post_date_gmt", "2026-10-16 08:43:57")
    child(post, "title", "Public sample title")
    cats = category_override or ("sex-educations", "sex-positions",
                                 "sex-tips-advice")
    for slug_value in cats:
        cat = ET.SubElement(post, "category",
                            {"domain": "category", "nicename": slug_value})
        cat.text = slug_value
    for key, val in (
        ("_thumbnail_id", featured_id),
        ("rank_math_title", "Example SEO %sep% %sitename%"),
        ("rank_math_focus_keyword", "Example focus"),
        ("rank_math_primary_category", "120"),
    ):
        meta = ET.SubElement(post, f"{{{WP}}}postmeta")
        child(meta, f"{{{WP}}}meta_key", key)
        child(meta, f"{{{WP}}}meta_value", val)
    body = "".join(
        '<!-- wp:image {"id":%d,"sizeSlug":"large"} -->'
        '<figure><img class="wp-image-%d" '
        'src="https://img.beyourlover.com/example/image-%d-1024x700.png"/></figure>'
        '<!-- /wp:image -->' % (x, x, x)
        for x in images
    )
    child(post, f"{{{CONTENT}}}encoded", body)
    for ident in (41549,) + IMAGE_IDS:
        if ident == missing_media:
            continue
        item = ET.SubElement(channel, "item")
        child(item, f"{{{WP}}}post_id", ident)
        child(item, f"{{{WP}}}post_type", "attachment")
        child(item, f"{{{WP}}}attachment_url",
              "https://img.beyourlover.com/example/image-%d.png" % ident)
    return ET.tostring(root, encoding="utf-8", xml_declaration=True)


class A3C2SourceOnlyTests(unittest.TestCase):
    def test_valid_source_manifest_is_not_write_authority(self):
        manifest = extract_manifest(fixture())
        self.assertEqual(manifest["source_post_id"], 41548)
        self.assertEqual(len(manifest["inline_images"]), 5)
        self.assertFalse(manifest["dev_write_authorized"])
        self.assertFalse(manifest["www_write_authorized"])
        self.assertIsNone(manifest["dev_attachment_ids"])
        self.assertNotIn("post_content", manifest)

    def test_slug_drift_holds(self):
        with self.assertRaisesRegex(ValueError, "slug mismatch"):
            extract_manifest(fixture(slug="wrong"))

    def test_category_drift_holds(self):
        with self.assertRaisesRegex(ValueError, "category list drift"):
            extract_manifest(fixture(category_override=("sex-positions",)))

    def test_missing_media_holds(self):
        with self.assertRaisesRegex(ValueError, "missing required media"):
            extract_manifest(fixture(missing_media=41549))

    def test_bad_featured_id_holds(self):
        with self.assertRaisesRegex(ValueError, "thumbnail ID changed"):
            extract_manifest(fixture(featured_id="99999"))

    def test_duplicate_inline_id_holds(self):
        with self.assertRaisesRegex(ValueError, "missing/duplicate"):
            extract_manifest(fixture(images=(41550,) * 5))

    def test_malformed_or_external_image_holds(self):
        with self.assertRaisesRegex(ValueError, "unexpected CDN"):
            cdn("https://attacker.invalid/image.png")
        with self.assertRaisesRegex(ValueError, "unexpected CDN"):
            cdn("http://img.beyourlover.com/image.png")

    def test_partial_block_holds(self):
        with self.assertRaisesRegex(ValueError, "expected exactly five"):
            blocks_from_content('<!-- wp:image {"id":41550} -->missing')

    def test_no_body_leak(self):
        result = extract_manifest(fixture())
        self.assertNotIn("wp:image", str(result))
        self.assertNotIn("html", result)
        self.assertFalse(result["a2_sku_write_authorized_by_this_script"])


if __name__ == "__main__":
    unittest.main()
