<?php
/**
 * Isolated Affiliate Dashboard contract test; no WordPress installation,
 * database, cookies, orders, payments, or mail required.
 * Run: php scripts/test-affiliate-dashboard.php
 */
declare(strict_types=1);

defined('ABSPATH') || define('ABSPATH', __DIR__.'/');
class WP_User {
    public int $ID; public string $first_name; public string $display_name;
    public function __construct(int $id, string $name = '') { $this->ID=$id; $this->first_name=$name; $this->display_name=$name; }
    public function exists(): bool { return $this->ID > 0; }
}
class WP_Query { public bool $is_404 = true; }
class BYL_Mock_DB {
    public function get_affiliate(int $id) { return $id === 7 ? (object)['user_id'=>21,'earnings'=>550,'unpaid_earnings'=>92,'visits'=>83,'referrals'=>4] : null; }
    public function get_referrals(array $args): array {
        if ($args['affiliate_id'] !== 7 || $args['number'] !== 13) { throw new RuntimeException('wrong referral query'); }
        if ($args['offset'] === 12) {
            return [(object)['affiliate_id'=>7,'referral_id'=>81,'date'=>'2026-09-01 12:00:00','amount'=>11,'status'=>'paid','currency'=>'USD']];
        }
        return [(object)['affiliate_id'=>7,'referral_id'=>88,'date'=>'2026-10-01 12:00:00','amount'=>19,'status'=>'paid','currency'=>'USD'],
            (object)['affiliate_id'=>9,'referral_id'=>99,'date'=>'2026-10-01 12:00:00','amount'=>9000,'status'=>'paid','currency'=>'USD']];
    }
    public function get_payouts(array $args): array {
        if ($args['affiliate_id'] !== 7 || $args['number'] !== 5) { throw new RuntimeException('wrong payout query'); }
        if ($args['offset'] === 4) { return [(object)['affiliate_id'=>7,'date'=>'2026-08-01 00:00:00','amount'=>25,'status'=>'paid']]; }
        return [(object)['affiliate_id'=>7,'date'=>'2026-09-01 00:00:00','amount'=>50,'status'=>'paid'],(object)['affiliate_id'=>9,'amount'=>10000]];
    }
    public function get_creatives(array $args): array { return []; }
}
$GLOBALS['byl_user'] = new WP_User(21,'Emma');
$GLOBALS['byl_hooks'] = [];
function add_filter($key,$fn,$p=10,$a=1) { $GLOBALS['byl_hooks'][$key][]=$fn; }
function add_action($key,$fn,$p=10,$a=1) { add_filter($key,$fn,$p,$a); }
function is_admin(): bool { return false; }
function wp_doing_ajax(): bool { return false; }
$GLOBALS['byl_is_affiliate_page'] = true;
function is_page($slug): bool { return $slug === 'my-affiliate' && $GLOBALS['byl_is_affiliate_page']; }
function wp_parse_url($url,$part=-1) { return parse_url($url,$part); }
function untrailingslashit($s) { return rtrim($s,'/'); }
function get_stylesheet_directory() { return dirname(__DIR__).'/theme/beyourlover-v3'; }
function get_stylesheet_directory_uri() { return '/theme/beyourlover-v3'; }
function home_url($path='/') { return 'https://dev.beyourlover.com'.$path; }
function wp_get_current_user() { return $GLOBALS['byl_user']; }
function affwp_get_currency() { return 'USD'; }
function affwp_get_affiliate_id($id=0) { return $id === 21 ? 7 : 0; }
function affwp_get_affiliate_status($id) { return $id === 7 ? 'active' : 'inactive'; }
function affwp_get_affiliate_referral_url($args=[]) { return 'https://dev.beyourlover.com/?ref=7'; }
function affiliate_wp() { static $db; if (!$db) { $db=new BYL_Mock_DB(); } return (object)['affiliates'=>$db,'referrals'=>$db,'payouts'=>$db,'creatives'=>$db]; }
function wp_http_validate_url($url) { return filter_var($url,FILTER_VALIDATE_URL) ? $url : false; }
function number_format_i18n($value,$decimals=0) { return number_format((float)$value,$decimals); }
function esc_html($s) { return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8'); }
function esc_attr($s) { return esc_html($s); }
function esc_url($s) { return esc_html($s); }
function add_query_arg($key,$value,$url) { return $url.'?'.rawurlencode($key).'='.rawurlencode((string)$value); }
function wp_logout_url($path='') { return home_url('/logout/'); }
function wc_get_page_permalink($type) { return home_url('/my-account/'); }
function byl_v3_account_url() { return home_url('/my-account/'); }
function wp_kses_post($html) { return strip_tags((string)$html,'<table><thead><tbody><tr><td><th>'); }
function shortcode_exists($name) { return $name==='affiliate_coupons'; }
function do_shortcode($code) { return '<table><tr><td>REALCODE</td></tr></table>'; }
function mysql2date($format,$value) { return date($format,strtotime($value)); }
function get_header() { $css=file_get_contents(dirname(__DIR__).'/theme/beyourlover-v3/assets/css/affiliate-dashboard.css'); echo '<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><title>Local synthetic UAT preview</title><style>'. $css .'</style></head><body style="margin:0;background:#fff"><header style="padding:16px 3%;background:#fff"><strong style="font:24px Georgia,serif;color:#81152a">BeYourLover</strong></header>'; }
function get_footer() { echo '</body></html>'; }
function user_can($u,$cap) { return false; }
require dirname(__DIR__).'/theme/beyourlover-v3/inc/affiliate-dashboard.php';
$assert = static function(bool $ok, string $message): void { if(!$ok) { throw new RuntimeException('FAIL '.$message); } echo 'PASS '.$message."\n"; };
$_SERVER['REQUEST_METHOD']='GET'; $_SERVER['REQUEST_URI']='/my-affiliate/';
$assert(byl_v3_affiliate_route(),'real WP Page GET dashboard route');
$_SERVER['REQUEST_METHOD']='POST';
$assert(!byl_v3_affiliate_route(),'POST route disallowed');
$_SERVER['REQUEST_METHOD']='GET';
$GLOBALS['byl_is_affiliate_page']=false;
$assert(!byl_v3_affiliate_route(),'non-affiliate WP Page disallowed');
$GLOBALS['byl_is_affiliate_page']=true;
$assert(!isset($GLOBALS['byl_hooks']['pre_handle_404']),'no virtual 404 or permalink override');
$assert(str_ends_with($GLOBALS['byl_hooks']['template_include'][0]('default.php'),'template-affiliate-dashboard.php'),'native page resolves existing dashboard template');
$active=byl_v3_affiliate_snapshot();
$assert($active['state']==='active' && $active['id']===7,'active affiliate detected from current user');
$assert(count($active['referrals'])===1 && count($active['payouts'])===1,'other affiliate records rejected');
$assert($active['referral_url']==='https://dev.beyourlover.com/?ref=7','tracked URL provided by engine');
$assert(byl_v3_affiliate_money(19.5,'USD')==='USD 19.50','currency label is explicit');
ob_start(); include dirname(__DIR__).'/theme/beyourlover-v3/template-affiliate-dashboard.php'; $render=ob_get_clean();
$assert(str_contains($render,'Hi, Emma') && str_contains($render,'USD 550.00'),'active user sees own real mock metrics');
if (in_array('--preview', $argv, true)) { file_put_contents(dirname(__DIR__).'/affiliate-preview.html', $render); }
$assert(!str_contains($render,'9000') && !str_contains($render,'10000'),'cross-affiliate values do not render');
$assert(str_contains($render,'REALCODE'),'assigned coupon shortcode is used');
$_GET['byl_ref_page']='2';
$_GET['byl_pay_page']='2';
$older=byl_v3_affiliate_snapshot();
$assert($older['referral_page']===2 && $older['payout_page']===2,'historical page cursors stay independent');
$assert(count($older['referrals'])===1 && $older['referrals'][0]->referral_id===81,'historical referrals are fetched with scoped offset');
$assert(count($older['payouts'])===1 && (float)$older['payouts'][0]->amount===25.0,'historical payouts are fetched with scoped offset');
ob_start(); include dirname(__DIR__).'/theme/beyourlover-v3/template-affiliate-dashboard.php'; $older_render=ob_get_clean();
$assert(str_contains($older_render,'Newer referrals') && str_contains($older_render,'Newer payouts'),'history links render only when applicable');
$assert(!str_contains($older_render,'9000') && !str_contains($older_render,'10000'),'historical pages do not expose other affiliates');
$_GET['byl_ref_page']=['7']; $_GET['byl_pay_page']='evil';
$assert(byl_v3_affiliate_page_number('byl_ref_page')===1 && byl_v3_affiliate_page_number('byl_pay_page')===1,'malformed history cursors fail closed');
unset($_GET['byl_ref_page'],$_GET['byl_pay_page']);
$GLOBALS['byl_user']=new WP_User(0);
$guest=byl_v3_affiliate_snapshot();
$assert($guest['state']==='guest' && !$guest['referrals'],'guest has no private records');
ob_start(); include dirname(__DIR__).'/theme/beyourlover-v3/template-affiliate-dashboard.php'; $render=ob_get_clean();
$assert(str_contains($render,'Sign In') && !str_contains($render,'USD 550.00'),'guest sees sign-in only');
$GLOBALS['byl_user']=new WP_User(35,'Customer');
$none=byl_v3_affiliate_snapshot();
$assert($none['state']==='unavailable','ordinary customer is denied affiliate data');
ob_start(); include dirname(__DIR__).'/theme/beyourlover-v3/template-affiliate-dashboard.php'; $render=ob_get_clean();
$assert(str_contains($render,'pending verification') && !str_contains($render,'USD 550.00'),'customer sees safe fallback');
echo "AFFILIATE_DASHBOARD_CONTRACT_PASS\n";
