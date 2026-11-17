<?php
/** Offline, no-WordPress, no-network smoke for BYL_TRANSLATION_DASHBOARD_V1. */
error_reporting(E_ALL);
define('ABSPATH', __DIR__ . '/');
$GLOBALS['byl_td_hooks'] = array();
$GLOBALS['byl_td_filter_result'] = null;

function wp_get_environment_type() { return getenv('BYL_TD_TEST_ENV') ?: 'staging'; }
function home_url() { return getenv('BYL_TD_TEST_HOME') ?: 'https://dev.beyourlover.com'; }
function untrailingslashit($value) { return rtrim($value, '/'); }
function add_action($name, $callback) { $GLOBALS['byl_td_hooks'][$name] = $callback; }
function get_option($name, $default = array()) {
    return $name === 'trp_settings' ? array('default-language'=>'en_US',
        'translation-languages'=>array('en_US','es_ES','de_DE','../evil')) : $default;
}
function wp_strip_all_tags($text) { return strip_tags($text); }
function get_permalink($post) { return 'https://dev.beyourlover.com/blog/' . $post->ID . '/'; }
function wp_parse_url($url, $component) { return parse_url($url, $component); }
function add_query_arg($key, $value, $url) { return $url . (strpos($url, '?') === false ? '?' : '&') . rawurlencode($key) . '=' . rawurlencode($value); }
function current_user_can($cap) { return $cap === 'manage_options'; }
function byl_trp_gate_active() { return !empty($GLOBALS['byl_td_gate_active']); }
function byl_trp_localized_url($post_id, $language) { return $GLOBALS['byl_td_approved_urls'][$language] ?? ''; }
function sanitize_key($value) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower($value)); }
function sanitize_text_field($value) { return trim((string) $value); }
function wp_unslash($value) { return stripslashes($value); }
function absint($value) { return abs((int) $value); }
function apply_filters($hook, $default, $post_id, $lang) { return $GLOBALS['byl_td_filter_result']; }

class BYL_TD_Test_Converter {
    public function get_url_for_language($language, $url, $marker) {
        if ($marker !== '') { throw new RuntimeException('Browser URL must not contain internal markers'); }
        return str_replace('https://dev.beyourlover.com/',
            'https://dev.beyourlover.com/' . strtolower(substr($language, 0, 2)) . '/', $url);
    }
}
class TRP_Translate_Press {
    public static function get_trp_instance() { return new self(); }
    public function get_component($component) {
        return $component === 'url_converter' ? new BYL_TD_Test_Converter() : null;
    }
}
class YBY_Module_Registry {
    public static $registered = array();
    public static $enabled = true;
    public static function register($id, $metadata) { self::$registered[$id] = $metadata; return true; }
    public static function is_enabled($id) { return $id === 'translation_dashboard' && self::$enabled; }
    public static function dependencies_met($id) { return self::is_enabled($id); }
}
class YBY_Andy_Admin_Shell_V22 {
    public static $invocation = array();
    public static function render($page, $heading, $body) {
        self::$invocation = array($page, $heading, $body);
    }
}

class WP_Query {
    public $args;
    public function __construct($args) { $this->args = $args; }
}
class BYL_TD_FakeDB {
    public $prefix = 'wp_';
    public $queries = array();
    public function esc_like($value) { return $value; }
    public function prepare($sql, $args) {
        $args = is_array($args) ? $args : array($args);
        return vsprintf($sql, array_map(static function ($value) {
            return "'" . str_replace("'", "''", (string) $value) . "'";
        }, $args));
    }
    public function get_var($sql) { return strpos($sql, 'es_es') !== false ? 'wp_trp_dictionary_en_us_es_es' : null; }
    public function get_results($sql, $mode) {
        $this->queries[] = $sql;
        return array(
            array('original'=>'Using the product safely','translated'=>'Uso seguro del producto','status'=>1),
            array('original'=>'Please review instructions carefully','translated'=>'Revise las instrucciones','status'=>2),
            array('original'=>'Cleaning and care information','translated'=>'','status'=>0),
        );
    }
}
$wpdb = new BYL_TD_FakeDB();
define('ARRAY_A', 'ARRAY_A');
require __DIR__ . '/../mu-plugins/byl-translation-dashboard.php';

function byl_td_assert($test, $label) {
    if (!$test) { fwrite(STDERR, "FAIL: {$label}\n"); exit(1); }
    echo "PASS: {$label}\n";
}

if (wp_get_environment_type() !== 'staging' || home_url() !== 'https://dev.beyourlover.com') {
    byl_td_assert(count($GLOBALS['byl_td_hooks']) === 0, 'No hooks on unauthorized environment or host');
    exit(0);
}

byl_td_assert(count($GLOBALS['byl_td_hooks']) === 4, 'Only Andy Core registration and admin/read-only hooks exist');
byl_td_register_andy_module();
$module = YBY_Module_Registry::$registered['translation_dashboard'] ?? array();
byl_td_assert(!empty($module) && $module['default_enabled'] === false
    && strpos($module['availability_message'], 'TranslatePress') !== false
    && $module['availability'] === 'byl_td_translatepress_available',
    'TranslatePress remains an optional, default-OFF Andy Core module with a prerequisite warning');
byl_td_assert(byl_td_module_enabled() === true, 'Available and enabled module exposes the dashboard');
YBY_Module_Registry::$enabled = false;
byl_td_assert(byl_td_module_enabled() === false, 'Turning module OFF gates dashboard access');
YBY_Module_Registry::$enabled = true;
byl_td_admin_page();
byl_td_assert(YBY_Andy_Admin_Shell_V22::$invocation === array('byl-translation-dashboard',
    '内容 · 翻译', 'byl_td_admin_page_body'),
    'Dashboard delegates once to Andy Core server-rendered Content L3 shell');
list($source, $langs) = byl_td_languages();
byl_td_assert($source === 'en_US' && $langs === array('es_ES','de_DE'), 'Languages derive from TranslatePress; invalid codes excluded');
byl_td_assert(byl_td_table('en_US', '../evil') === '', 'Reject unsafe dictionary table identifier');
byl_td_assert(byl_td_table('en_US', 'de_DE') === '', 'Unknown language table is not invented');

$post = (object) array('ID'=>38168, 'post_title'=>'Using the product safely', 'post_content'=>
    '<p>Please review instructions carefully</p><p>Cleaning and care information</p>');
byl_td_assert(byl_td_frontend_url($post, 'es_ES') === 'https://dev.beyourlover.com/es/blog/38168/',
    'Frontend locale link derives from the installed TranslatePress converter');
byl_td_assert(byl_td_frontend_url($post, 'en_US') === '' && byl_td_frontend_url($post, '../evil') === '',
    'Source and unconfigured locale URLs are never invented');
$GLOBALS['byl_td_gate_active'] = true;
byl_td_assert(byl_td_frontend_url($post, 'es_ES') === 'https://dev.beyourlover.com/es/blog/38168/?byl-trp-preview=1',
    'Unapproved locale uses existing edit_post-only Dev SEO Gate preview');
$GLOBALS['byl_td_approved_urls']['es_ES'] = 'https://dev.beyourlover.com/es/blog/reviewed-spanish-url/';
byl_td_assert(byl_td_frontend_url($post, 'es_ES') === 'https://dev.beyourlover.com/es/blog/reviewed-spanish-url/',
    'Reviewed locale uses the exact approved URL without a preview override');
$GLOBALS['byl_td_approved_urls']['es_ES'] = 'https://www.beyourlover.com/es/blog/untrusted/';
byl_td_assert(byl_td_frontend_url($post, 'es_ES') === 'https://dev.beyourlover.com/es/blog/38168/?byl-trp-preview=1',
    'Only Dev-host approved URLs can be surfaced');
$post->post_status = 'draft';
byl_td_assert(byl_td_frontend_url($post, 'es_ES') === '', 'Draft source never gets a misleading frontend link');
$post->post_status = 'publish';
$GLOBALS['byl_td_gate_active'] = false;
$inspection = byl_td_inspect($post, 'en_US','es_ES');
byl_td_assert($inspection['recognized'] === 3 && $inspection['translated'] === 2,
    'Read only dictionary status is classified from original/translated/status');
byl_td_assert($inspection['machine'] === 1 && $inspection['reviewed'] === 1,
    'Machine and human-reviewed dictionary rows are distinct');
byl_td_assert($inspection['percent'] === 67 && $inspection['code'] === 'partial',
    'Exact-match sample ratio is an estimate, not full article completion');

$GLOBALS['byl_td_filter_result'] = array('structure_pass'=>true, 'dev_qa95'=>'PENDING');
byl_td_assert(byl_td_qa(38168,'es_ES')['pass'] === false, 'Offline Worker structure PASS never implies Dev QA95');
$GLOBALS['byl_td_filter_result'] = array('score'=>95, 'p0_count'=>0, 'dev_http_readback_200'=>true);
byl_td_assert(byl_td_qa(38168,'es_ES')['pass'] === true, 'QA95 requires score, zero blockers and Dev HTTP readback');
$GLOBALS['byl_td_filter_result']['dev_http_readback_200'] = false;
byl_td_assert(byl_td_qa(38168,'es_ES')['pass'] === false, 'Unverified Dev route cannot be QA95 PASS');

$_GET = array('byl_search'=>'#39066','byl_type'=>'post','paged'=>'2');
$query = byl_td_query();
byl_td_assert($query->args['post__in'] === array(39066) && $query->args['paged'] === 2,
    'Article ID search and paging are safe and deterministic');
echo "BYL_TRANSLATION_DASHBOARD_V1 offline smoke: PASS\n";
