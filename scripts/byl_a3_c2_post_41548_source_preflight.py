#!/usr/bin/env python3
"""A3-C2 source-only preflight for the exact WWW post 41548.

Reads the Owner's original local WXR file and emits a public-metadata-only
manifest. No network calls, database calls, media downloads, or WP writes.
The article body and all private WXR comments remain local and are never
included in stdout or the generated JSON manifest.
"""
import argparse
import hashlib
from html.parser import HTMLParser
import json
from pathlib import Path
import re
import sys
from urllib.parse import urlparse
import xml.etree.ElementTree as ET

EXPECTED_SHA256 = "b4e17b669b2f7071514ddd8a8e959f2f4372df8280df6b8201063c9761eb5453"
POST_ID = "41548"
SLUG = "sex-during-pregnancy"
EXPECTED_IMAGE_IDS = {41550, 41551, 41552, 41553, 41554}
EXPECTED_CATEGORIES = {"sex-educations", "sex-positions", "sex-tips-advice"}
ATTACHMENT_IDS = {41549} | EXPECTED_IMAGE_IDS
NS = {"wp": "http://wordpress.org/export/1.2/",
      "content": "http://purl.org/rss/1.0/modules/content/"}
IMAGE_BLOCK = re.compile(
    r"<!--\s*wp:image\s+(\{.*?\})\s*-->(.*?)<!--\s*/wp:image\s*-->",
    re.DOTALL,
)


def must(condition, message):
    if not condition:
        raise ValueError("A3-C2 HOLD: " + message)


def cdn(url):
    parsed = urlparse(url)
    must(parsed.scheme == "https" and parsed.hostname == "img.beyourlover.com",
         "unexpected CDN image URL")
    must(not parsed.username and not parsed.password and not parsed.fragment,
         "unexpected image URL components")
    return url


def xml_text(element, name, default=""):
    return element.findtext(name, default=default, namespaces=NS)


class OneImage(HTMLParser):
    def __init__(self):
        super().__init__()
        self.images = []

    def handle_starttag(self, tag, attrs):
        if tag == "img":
            self.images.append(dict(attrs))


def blocks_from_content(content):
    images = []
    for m in IMAGE_BLOCK.finditer(content):
        attrs = json.loads(m.group(1))
        ident = attrs.get("id")
        must(isinstance(ident, int) and ident in EXPECTED_IMAGE_IDS,
             "unexpected Gutenberg image-block ID")
        parser = OneImage()
        parser.feed(m.group(2))
        must(len(parser.images) == 1, "image block must have exactly one img")
        img = parser.images[0]
        must("wp-image-" + str(ident) in img.get("class", "").split(),
             "HTML image class/old block ID mismatch")
        images.append({"source_attachment_id": ident,
                       "inline_cdn_url": cdn(img.get("src", ""))})
    must(len(images) == 5, "expected exactly five Gutenberg image blocks")
    must({i["source_attachment_id"] for i in images} == EXPECTED_IMAGE_IDS,
         "missing/duplicate source inline image IDs")
    must(len(set(i["inline_cdn_url"] for i in images)) == 5,
         "unexpected duplicate inline URL")
    return images


def extract_manifest(xml_bytes):
    # Original exporter has a harmless whitespace/BOM preamble.
    stripped = xml_bytes.lstrip(b"\xef\xbb\xbf \t\r\n")
    must(stripped.startswith(b"<?xml"), "XML declaration absent")
    root = ET.fromstring(stripped)
    items = root.findall(".//item")
    posts = [x for x in items if xml_text(x, "wp:post_id") == POST_ID
             and xml_text(x, "wp:post_type") == "post"]
    must(len(posts) == 1, "exact post 41548 not unique")
    post = posts[0]
    must(xml_text(post, "wp:post_name") == SLUG, "source slug mismatch")
    must(xml_text(post, "wp:status") == "publish", "source status mismatch")
    date = xml_text(post, "wp:post_date_gmt")
    must(bool(re.fullmatch(r"\d{4}-\d\d-\d\d \d\d:\d\d:\d\d", date)),
         "source UTC publication date invalid")
    categories = {x.get("nicename") for x in post.findall("category")
                  if x.get("domain") == "category"}
    tags = {x.get("nicename") for x in post.findall("category")
            if x.get("domain") == "post_tag"}
    must(categories == EXPECTED_CATEGORIES, "category list drift")
    must(not tags, "tag list drift")
    meta = {xml_text(x, "wp:meta_key"): xml_text(x, "wp:meta_value")
            for x in post.findall("wp:postmeta", NS)}
    must(meta.get("_thumbnail_id") == "41549", "source thumbnail ID changed")
    for key in ("rank_math_title", "rank_math_focus_keyword",
                "rank_math_primary_category"):
        must(bool(meta.get(key)), "source SEO field absent: " + key)
    content = xml_text(post, "content:encoded")
    must(bool(content), "empty original post body")
    images = blocks_from_content(content)

    attachments = {}
    for item in items:
        ident = xml_text(item, "wp:post_id")
        if ident in {str(x) for x in ATTACHMENT_IDS}:
            must(xml_text(item, "wp:post_type") == "attachment",
                 "source media ID is not an attachment")
            must(ident not in attachments, "duplicate source media ID")
            attachments[ident] = cdn(xml_text(item, "wp:attachment_url"))
    must(len(attachments) == 6, "source WXR missing required media items")
    for img in images:
        img["original_cdn_url"] = attachments[str(img["source_attachment_id"])]

    return {
        "gate": "SOURCE_ONLY_PREFLIGHT_PASS_NOT_IMPORT_AUTHORITY",
        "source_post_id": 41548,
        "source_slug": SLUG,
        "source_title": xml_text(post, "title"),
        "source_status": "publish",
        "source_publication_date_gmt": date,
        "source_content_sha256": hashlib.sha256(content.encode("utf-8")).hexdigest(),
        "source_content_length": len(content),
        "categories_by_slug": sorted(categories),
        "tags_by_slug": sorted(tags),
        "seo": {"title": meta["rank_math_title"],
                "focus_keyword": meta["rank_math_focus_keyword"],
                "source_primary_category_id": meta["rank_math_primary_category"]},
        "featured_image": {"source_attachment_id": 41549,
                           "original_cdn_url": attachments["41549"]},
        "inline_images": images,
        "dev_attachment_ids": None,
        "dev_post_id": None,
        "dev_write_authorized": False,
        "www_write_authorized": False,
        "a2_sku_write_authorized_by_this_script": False,
    }


def main():
    ap = argparse.ArgumentParser(description=__doc__)
    ap.add_argument("--wxr", required=True, type=Path)
    ap.add_argument("--output", type=Path,
                    help="Optional local sanitized JSON output (never the article body)")
    args = ap.parse_args()
    blob = args.wxr.read_bytes()
    must(hashlib.sha256(blob).hexdigest() == EXPECTED_SHA256,
         "original WXR checksum mismatch")
    manifest = extract_manifest(blob)
    manifest["original_wxr_sha256"] = EXPECTED_SHA256
    payload = json.dumps(manifest, ensure_ascii=False, indent=2) + "\n"
    if args.output:
        must(not args.output.exists(), "refusing to overwrite existing manifest")
        args.output.write_text(payload, encoding="utf-8")
    else:
        sys.stdout.write(payload)


if __name__ == "__main__":
    try:
        main()
    except (ValueError, ET.ParseError, OSError, json.JSONDecodeError) as exc:
        print(str(exc), file=sys.stderr)
        sys.exit(2)
