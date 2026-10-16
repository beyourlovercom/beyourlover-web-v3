<?php
/** Offline-only identity/authorization tests for WWW read-only creator bridge. */
declare(strict_types=1);
define('ABSPATH', __DIR__.'/');
define('BYL_CREATOR_REMOTE_ENABLED', true);
define('BYL_CREATOR_REMOTE_KEY_ID', 'creator-dev-v1');
define('BYL_CREATOR_REMOTE_SIGN_KEY', str_repeat('S', 64));
define('BYL_CREATOR_BINDING_KEY', str_repeat('B', 64));
class WP_User {
    public int $ID;
    public function __construct(int $id) { $this->ID = $id; }
}
function user_can($user, $cap) { return $user->ID === 1; }
function wp_http_validate_url($url) { return filter_var($url, FILTER_VALIDATE_URL) ? $url : false; }
function wp_parse_url($url,$component=-1) { return parse_url($url,$component); }
function byl_v3_affiliate_page_number($key) { return 1; }
$GLOBALS['calls']=0;
$GLOBALS['mapping']=[];
$GLOBALS['mock_response']=[];
function get_user_meta($id,$key,$single) { return $GLOBALS['mapping'][$id] ?? null; }
function is_wp_error($response) { return false; }
function wp_remote_retrieve_response_code($response) { return $response['status'] ?? 500; }
function wp_remote_retrieve_body($response) { return json_encode($response['payload'] ?? []); }
function wp_remote_get($url,$opts) {
    $GLOBALS['calls']++;
    if (strpos($url,'https://www.beyourlover.com/wp-json/andy-core/v1/creator/history?dev_user_id=21&') !== 0) {
        throw new RuntimeException('Unsafe host');
    }
    if ($opts['redirection'] !== 0 || !$opts['sslverify'] ||
        !isset($opts['headers']['X-BYL-Creator-Signature'])) { throw new RuntimeException('Not signed or unsafe request'); }
    $path = substr($url,strlen('https://www.beyourlover.com'));
    $headers = $opts['headers'];
    $expected = hash_hmac('sha256', "GET
".$path."
".$headers['X-BYL-Creator-Time']."
".$headers['X-BYL-Creator-Nonce']."
".$headers['X-BYL-Creator-Key'], BYL_CREATOR_REMOTE_SIGN_KEY);
    if (!hash_equals($expected,$headers['X-BYL-Creator-Signature'])) { throw new RuntimeException('Signature mismatch'); }
    return $GLOBALS['mock_response'];
}
$bridgeSource = file_get_contents(dirname(__DIR__).'/theme/beyourlover-v3/inc/affiliate-remote-bridge.php');
if (strpos($bridgeSource, '"GET\n"') === false) { throw new RuntimeException('FAIL signed canonical linefeeds must be PHP escapes, not physical source newlines'); }
require dirname(__DIR__).'/theme/beyourlover-v3/inc/affiliate-remote-bridge.php';
function check($cond,$message) { if (!$cond) { throw new RuntimeException('FAIL '.$message); } echo 'PASS '.$message."
"; }
$user=new WP_User(21);
check(byl_v3_creator_remote_snapshot($user)===null && $GLOBALS['calls']===0, 'missing binding blocks network');
$GLOBALS['mapping'][21]=[
 'www_user_id'=>55, 'affiliate_id'=>99,
 'proof'=>byl_v3_creator_binding_proof(21,55,99),
];
$GLOBALS['mock_response']=['status'=>200,'payload'=>['ok'=>true,'data'=>[
 'affiliate_id'=>99,'wp_user_id'=>55,'status'=>'active','currency'=>'USD',
 'account'=>['earnings'=>'500.5','unpaid_earnings'=>'50','visits'=>88,'referrals'=>4],
 'referrals'=>[['affiliate_id'=>99,'referral_id'=>10,'amount'=>'12.5','status'=>'paid','currency'=>'USD']],
 'payouts'=>[['affiliate_id'=>99,'amount'=>'40','status'=>'paid']],
 'more_referrals'=>false,'more_payouts'=>false,
 'referral_url'=>'https://www.beyourlover.com/?ref=99',
]]];
$r=byl_v3_creator_remote_snapshot($user);
check($r!==null && $r['state']==='active' && $r['id']===99 && count($r['referrals'])===1,'matched user sees own remote AffiliateWP snapshot');
check($r['account']->earnings==='500.5' && $r['currency']==='USD','totals are provider-sourced and currency preserved');
$GLOBALS['mock_response']['payload']['data']['referrals'][]=['affiliate_id'=>100,'referral_id'=>11,'amount'=>'999'];
check(byl_v3_creator_remote_snapshot($user)===null,'cross-affiliate injected referral fails whole snapshot');
array_pop($GLOBALS['mock_response']['payload']['data']['referrals']);
$GLOBALS['mock_response']['payload']['data']['wp_user_id']=56;
check(byl_v3_creator_remote_snapshot($user)===null,'wrong WWW owner identity denied');
$GLOBALS['mock_response']['payload']['data']['wp_user_id']=55;
$GLOBALS['mapping'][21]['affiliate_id']=100;
$calls=$GLOBALS['calls'];
check(byl_v3_creator_remote_snapshot($user)===null && $GLOBALS['calls']===$calls,'tampered binding proof fails before network');
$GLOBALS['mapping'][21]['affiliate_id']=99;
check(byl_v3_creator_remote_snapshot(new WP_User(1))===null,'WordPress admin is never a creator viewer');
check(byl_v3_creator_remote_snapshot(new WP_User(90))===null,'unbound ordinary customer denied');
echo "CREATOR_READONLY_BRIDGE_SECURITY_PASS
";
