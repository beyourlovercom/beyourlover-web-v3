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
$GLOBALS['mock_response_second']=[];
function get_user_meta($id,$key,$single) { return $GLOBALS['mapping'][$id] ?? null; }
function is_wp_error($response) { return false; }
function wp_remote_retrieve_response_code($response) { return $response['status'] ?? 500; }
function wp_remote_retrieve_body($response) { return json_encode($response['payload'] ?? []); }
function wp_remote_get($url,$opts) {
    $GLOBALS['calls']++;
    if (strpos($url,'https://www.beyourlover.com/wp-json/andy-core/v1/creator/history?dev_user_id=21&') !== 0 &&
        strpos($url,'https://www.beyourlover.com/wp-json/andy-core/v1/creator/history?dev_user_id=22&') !== 0) {
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
    return strpos($url,'https://www.beyourlover.com/wp-json/andy-core/v1/creator/history?dev_user_id=22&')===0 ?
        $GLOBALS['mock_response_second'] : $GLOBALS['mock_response'];
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
$GLOBALS['mock_response']['payload']['data']['payouts'][]=['affiliate_id'=>100,'amount'=>'9999','status'=>'paid'];
check(byl_v3_creator_remote_snapshot($user)===null,'cross-affiliate injected payout fails whole snapshot');
array_pop($GLOBALS['mock_response']['payload']['data']['payouts']);
$GLOBALS['mock_response']['payload']['data']['affiliate_id']=100;
check(byl_v3_creator_remote_snapshot($user)===null,'wrong top-level affiliate identity denied');
$GLOBALS['mock_response']['payload']['data']['affiliate_id']=99;
$GLOBALS['mock_response']['payload']['data']['status']='inactive';
check(byl_v3_creator_remote_snapshot($user)===null,'inactive WWW affiliate cannot receive history');
$GLOBALS['mock_response']['payload']['data']['status']='active';
$GLOBALS['mock_response']['payload']['data']['currency']='INVALID';
check(byl_v3_creator_remote_snapshot($user)===null,'invalid WWW currency denies entire financial snapshot');
$GLOBALS['mock_response']['payload']['data']['currency']='USD';
$GLOBALS['mock_response']['payload']['data']['referral_url']='https://untrusted.example.net/?ref=99';
$invalidLink=byl_v3_creator_remote_snapshot($user);
check(is_array($invalidLink) && $invalidLink['referral_url']==='','foreign referral host suppressed; never used as public link');
$GLOBALS['mock_response']['payload']['data']['referral_url']='https://www.beyourlover.com/?ref=99';
$GLOBALS['mock_response']['status']=401;
check(byl_v3_creator_remote_snapshot($user)===null,'WWW unauthorized response fails closed');
$GLOBALS['mock_response']['status']=200;
$existingCount=count($GLOBALS['mock_response']['payload']['data']['referrals']);
for($j=0;$j<12;$j++) {
    $GLOBALS['mock_response']['payload']['data']['referrals'][]=[
        'affiliate_id'=>99,'referral_id'=>100+$j,'amount'=>'1.00','status'=>'paid'
    ];
}
check(byl_v3_creator_remote_snapshot($user)===null,'oversized remote referral payload rejected before rendering');
$GLOBALS['mock_response']['payload']['data']['referrals']=array_slice(
    $GLOBALS['mock_response']['payload']['data']['referrals'],0,$existingCount
);
$GLOBALS['mapping'][21]['affiliate_id']=100;
$calls=$GLOBALS['calls'];
check(byl_v3_creator_remote_snapshot($user)===null && $GLOBALS['calls']===$calls,'tampered binding proof fails before network');
$GLOBALS['mapping'][21]['affiliate_id']=99;
check(byl_v3_creator_remote_snapshot(new WP_User(1))===null,'WordPress admin is never a creator viewer');
check(byl_v3_creator_remote_snapshot(new WP_User(90))===null,'unbound ordinary customer denied');

// Isolated two-creator client-bridge fixture; fake IDs, fake HMAC keys.
// No production map is changed and no real WWW endpoint is called.
$GLOBALS['mapping'][22]=[
 'www_user_id'=>56, 'affiliate_id'=>100,
 'proof'=>byl_v3_creator_binding_proof(22,56,100),
];
$GLOBALS['mock_response_second']=['status'=>200,'payload'=>['ok'=>true,'data'=>[
 'affiliate_id'=>100,'wp_user_id'=>56,'status'=>'active','currency'=>'USD',
 'account'=>['earnings'=>'91.92','unpaid_earnings'=>'85.62','visits'=>99,'referrals'=>1],
 'referrals'=>[['affiliate_id'=>100,'referral_id'=>8202,'amount'=>'22.22','status'=>'unpaid','currency'=>'USD']],
 'payouts'=>[['affiliate_id'=>100,'amount'=>'4.24','status'=>'paid']],
 'more_referrals'=>false,'more_payouts'=>false,
 'referral_url'=>'https://www.beyourlover.com/ref/100/',
]]];
$syntheticA=byl_v3_creator_remote_snapshot(new WP_User(21));
$syntheticB=byl_v3_creator_remote_snapshot(new WP_User(22));
check($syntheticA!==null && $syntheticB!==null &&
      $syntheticA['id']===99 && $syntheticB['id']===100 &&
      $syntheticA['account']->earnings==='500.5' &&
      $syntheticB['account']->earnings==='91.92',
      'two synthetic Dev users bind to different WWW affiliates');
check(count($syntheticA['referrals'])===1 && count($syntheticB['referrals'])===1 &&
      $syntheticA['referrals'][0]->referral_id===10 &&
      $syntheticB['referrals'][0]->referral_id===8202,
      'two synthetic referral histories never cross');
check(count($syntheticA['payouts'])===1 && count($syntheticB['payouts'])===1 &&
      $syntheticA['payouts'][0]->amount==='40' &&
      $syntheticB['payouts'][0]->amount==='4.24',
      'two synthetic payout histories never cross');
$GLOBALS['mock_response_second']['payload']['data']['affiliate_id']=99;
check(byl_v3_creator_remote_snapshot(new WP_User(22))===null,
      'synthetic Creator B cannot receive Creator A account');
$GLOBALS['mock_response_second']['payload']['data']['affiliate_id']=100;
$GLOBALS['mock_response_second']['payload']['data']['payouts'][]=['affiliate_id'=>99,'amount'=>'9999'];
check(byl_v3_creator_remote_snapshot(new WP_User(22))===null,
      'synthetic Creator B rejects leaked A payout');
array_pop($GLOBALS['mock_response_second']['payload']['data']['payouts']);
$GLOBALS['mapping'][22]['www_user_id']=55;
$calls=$GLOBALS['calls'];
check(byl_v3_creator_remote_snapshot(new WP_User(22))===null &&
      $calls===$GLOBALS['calls'],
      'synthetic swapped user proof fails before network');
$GLOBALS['mapping'][22]['www_user_id']=56;
check(byl_v3_creator_remote_snapshot(new WP_User(21))!==null &&
      byl_v3_creator_remote_snapshot(new WP_User(22))!==null,
      'both synthetic creators recover after denied attempts');
echo "CREATOR_TWO_IDENTITY_CLIENT_BRIDGE_PASS\n";
echo "CREATOR_READONLY_BRIDGE_SECURITY_PASS
";
