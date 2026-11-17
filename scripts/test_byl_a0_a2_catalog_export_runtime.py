"""Isolated synthetic Woo/PHP runtime test for safe v2 A0 export.

This never accesses a WordPress installation or live WWW/Dev.
"""
import json
import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import unittest

SCRIPT = Path(__file__).with_name("byl_a0_a2_catalog_export.php")
FAKE_WP = r"""<?php
define('WP_CLI', true);
define('ABSPATH', '/synthetic/wordpress/');
define('WC_VERSION', 'synthetic-9.9');
function untrailingslashit($s) { return rtrim($s, '/'); }
function home_url() { return getenv('SYNTHETIC_SITE'); }
function wp_get_environment_type() { return home_url()==='https://dev.beyourlover.com' ? 'staging' : 'production'; }
function get_bloginfo($key) { return '6.9-synthetic'; }
function get_post_status($id) { return $id===202 ? 'inherit' : 'publish'; }
function wp_get_post_terms($id,$taxonomy,$args) { return []; }
function is_wp_error($v) { return false; }
function wp_get_attachment_url($id) { return false; }
function get_post_meta($id,$key,$single) { return ''; }
function wp_reset_postdata() {}
function wp_json_encode($value,$flags) { return json_encode($value,$flags); }
class WC_Product_Attribute {}
class SyntheticProduct {
    private $id;
    function __construct($id) { $this->id=$id; }
    function get_type() { return $this->id===202 ? 'variation' : 'variable'; }
    function is_type($type) { return $this->get_type()===$type; }
    function get_parent_id() { return $this->id===202 ? 101 : 0; }
    function get_id() { return $this->id; }
    function get_sku($context='view') {
        if ($this->id===101) { return 'PARENT-SKU'; }
        return $context==='view' ? 'PARENT-SKU' : '';
    }
    function get_status() { return 'publish'; }
    function get_attributes() { return $this->id===202 ? ['pa_color'=>'red'] : []; }
    function get_gallery_image_ids() { return []; }
    function get_image_id() { return 0; }
    function get_regular_price() { return '10'; }
    function get_sale_price() { return ''; }
}
function wc_get_product($id) {
    if (in_array($id,[101,202],true)) { return new SyntheticProduct($id); }
    return null;
}
class WP_Query {
    public $posts;
    public $max_num_pages=1;
    function __construct($args) {
        if (!in_array('inherit',$args['post_status'],true)) {
            throw new Exception('MISSING_INHERIT_VARIATIONS');
        }
        if (!in_array('product_variation',$args['post_type'],true)) {
            throw new Exception('MISSING_VARIATION_POST_TYPE');
        }
        $this->posts = $args['paged'] === 1 ? [101,202] : [];
    }
}
require $argv[1];
"""


@unittest.skipUnless(shutil.which("php"), "Synthetic PHP runtime requires PHP CLI")
class CatalogExportRuntimeTest(unittest.TestCase):
    def run_export(self, site):
        with tempfile.TemporaryDirectory(prefix="byl-a0-synthetic-") as temp:
            runner = Path(temp) / "wp-fixture.php"
            runner.write_text(FAKE_WP)
            return subprocess.run(
                ["php", str(runner), str(SCRIPT)],
                env={**os.environ, "SYNTHETIC_SITE": site},
                capture_output=True, text=True, timeout=5
            )

    def test_variation_inherit_post_status_and_stored_sku_not_parent_view(self):
        result = self.run_export("https://dev.beyourlover.com")
        self.assertEqual(result.returncode, 0, result.stderr)
        data = json.loads(result.stdout)
        self.assertEqual(data["schema"], "byl-catalog-a0-v2")
        self.assertEqual(data["counts"]["records"], 2)
        self.assertEqual(data["counts"]["product_variations"], 1)
        items = {x["id"]: x for x in data["products"]}
        self.assertEqual(items[101]["sku"], "PARENT-SKU")
        self.assertEqual(items[202]["sku"], "")
        self.assertEqual(items[202]["parent_sku"], "PARENT-SKU")
        self.assertEqual(items[202]["sku_origin"], "stored")
        self.assertEqual(items[202]["raw_post_status"], "inherit")
        self.assertEqual(items[202]["attributes"], {"pa_color": ["red"]})

    def test_www_authoritative_site_is_read_only_allowlisted(self):
        result = self.run_export("https://www.beyourlover.com")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(json.loads(result.stdout)["site"], "https://www.beyourlover.com")

    def test_unapproved_host_is_rejected_without_export(self):
        result = self.run_export("https://unapproved.example")
        self.assertEqual(result.returncode, 2)
        self.assertIn("BYL_A0_A2_DENY", result.stderr)
        self.assertEqual(result.stdout, "")

    def test_no_writes_or_external_call_in_export_source(self):
        import re
        source = SCRIPT.read_text()
        for fn in ("wp_insert_post", "wp_update_post", "update_post_meta",
                   "update_option", "delete_option", "wp_mail",
                   "wp_remote_post", "wp_remote_get", "curl_exec",
                   "file_put_contents", "wc_create_order"):
            self.assertFalse(re.search(r"\b" + re.escape(fn) + r"\s*\(", source),
                             msg="Unexpected write/network function: " + fn)


if __name__ == "__main__":
    unittest.main()
