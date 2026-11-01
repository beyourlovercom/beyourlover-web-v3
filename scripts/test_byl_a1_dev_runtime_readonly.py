"""Isolated fake WordPress/PHP tests for sanitized A1 WP-CLI inspector."""
import json
import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import unittest


SCRIPT = Path(__file__).with_name("byl_a1_dev_runtime_readonly.php")


@unittest.skipUnless(shutil.which("php"), "PHP CLI not installed in synthetic CI")
class DevRuntimeReadOnlyTests(unittest.TestCase):
    def run_fixture(self, site="https://dev.beyourlover.com", envtype="staging"):
        with tempfile.TemporaryDirectory(prefix="byl-a1-fixture-") as root:
            base = Path(root)
            (base / "wp-admin" / "includes").mkdir(parents=True)
            (base / "mu").mkdir()
            (base / "mu" / "byl-dev-safety.php").write_text("<?php // fake mu plugin\n")
            (base / "wp-admin" / "includes" / "plugin.php").write_text(
                "<?php function get_plugins(){return ["
                "'woocommerce/woocommerce.php'=>['Version'=>'9.0-fixture']"
                "];}\n"
            )
            php = """<?php
define('WP_CLI', true);
define('ABSPATH', __ABS__);
define('WPMU_PLUGIN_DIR', __MU__);
define('WC_VERSION', '9.0-fixture');
function untrailingslashit($s){return rtrim($s, '/');}
function home_url(){return getenv('FAKE_SITE');}
function wp_get_environment_type(){return getenv('FAKE_ENVTYPE');}
function get_option($name,$default=null){
 $options=[
  'blog_public'=>'0',
  'active_plugins'=>['woocommerce/woocommerce.php'],
  'woocommerce_woocommerce_payments_settings'=>[
    'enabled'=>'yes','test_mode'=>'yes',
    'secret_key'=>'DO_NOT_EXPOSE_TEST_PAYMENT_SECRET'
  ],
  'woocommerce_stripe_settings'=>[
    'enabled'=>'no','testmode'=>'no','secret_key'=>'DO_NOT_EXPOSE_TEST_STRIPE'
  ],
  'woocommerce_ppcp_api_settings'=>[
    'environment'=>'sandbox','client_secret'=>'DO_NOT_EXPOSE_PAYPAL'
  ],
  'woocommerce_ppcp_settings'=>['enabled'=>'no'],
  'cron'=>['version'=>2,111=>['a'=>['hash'=>[]],'b'=>['hash'=>[]]]],
 ];
 return $options[$name] ?? $default;
}
function get_bloginfo($name){return '6.0-fixture';}
function get_stylesheet(){return 'byl-fixture';}
function has_filter($hook){return $hook==='pre_wp_mail'?99:1;}
function has_action($hook){return 10;}
function wp_json_encode($v,$flags){return json_encode($v,$flags);}
function byl_dev_p13_checkout_gate(){return [];}
function byl_dev_p13_runtime_ready(){return true;}
require $argv[1];
"""
            php = php.replace("__ABS__", json.dumps(str(base) + "/"))
            php = php.replace("__MU__", json.dumps(str(base / "mu")))
            runner = base / "fixture.php"
            runner.write_text(php)
            env = {**os.environ, "FAKE_SITE": site, "FAKE_ENVTYPE": envtype}
            return subprocess.run(
                ["php", str(runner), str(SCRIPT)], env=env,
                capture_output=True, text=True, timeout=5,
            )

    def test_sanitized_dev_runtime_receipt_never_emits_secrets(self):
        p = self.run_fixture()
        self.assertEqual(p.returncode, 0, p.stderr)
        d = json.loads(p.stdout)
        self.assertEqual(d["scope"], "DEV_ONLY_AUTHORIZED_WPCLI_SNAPSHOT_NOT_EXTERNAL_PROVIDER_ATTESTATION")
        self.assertEqual(d["runtime"]["woo_version"], "9.0-fixture")
        self.assertTrue(d["dev_safety"]["mu_file_present_readable"])
        self.assertTrue(d["dev_safety"]["runtime_sentinel_present"])
        self.assertEqual(d["payment_config_hints_only"]["woocommerce_payments"]["test_mode"], "TEST")
        self.assertEqual(d["payment_config_hints_only"]["stripe"]["test_mode"], "LIVE_OR_UNSAFE")
        self.assertEqual(d["scheduled_work"]["wp_cron_event_records"], 2)
        self.assertEqual(d["a1_overall"], "HOLD")
        self.assertIs(d["a2_import_authorized"], False)
        for sensitive in [
            "DO_NOT_EXPOSE_TEST_PAYMENT_SECRET", "DO_NOT_EXPOSE_TEST_STRIPE",
            "DO_NOT_EXPOSE_PAYPAL", str(SCRIPT),
        ]:
            self.assertNotIn(sensitive, p.stdout)

    def test_wrong_www_host_is_denied_without_probing_options(self):
        p = self.run_fixture(site="https://www.beyourlover.com")
        self.assertEqual(p.returncode, 2)
        self.assertIn("BYL_A1_DENY", p.stderr)
        self.assertNotIn("secret", p.stderr.lower())

    def test_nonstaging_dev_is_denied(self):
        p = self.run_fixture(envtype="production")
        self.assertEqual(p.returncode, 2)
        self.assertIn("BYL_A1_DENY", p.stderr)

    def test_php_source_never_exposes_active_mutation_primitives(self):
        s = SCRIPT.read_text()
        import re
        for call in ("update_option", "delete_option", "wp_insert_post",
                     "wp_mail", "wp_remote_post", "wp_remote_get", "wp_schedule_event",
                     "wc_create_order", "file_put_contents", "curl_exec"):
            self.assertFalse(re.search(r"\\b" + re.escape(call) + r"\\s*\\(", s),
                             msg="Unexpected mutation or external network call: " + call)


if __name__ == "__main__":
    unittest.main()
