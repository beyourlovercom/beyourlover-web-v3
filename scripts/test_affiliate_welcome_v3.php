<?php
// Offline provider-contract test. No WP DB, HTTP, SMTP or real recipient.
define('ABSPATH', __DIR__);
define('BYL_AFFILIATE_WELCOME_V3_ENABLED',true);
$GLOBALS['host']='dev.beyourlover.com';
$GLOBALS['opts']=[]; $GLOBALS['mails']=[]; $GLOBALS['hooks']=[]; $GLOBALS['reset_count']=0; $GLOBALS['send_ok']=true;
$GLOBALS['coupon_amount']='17.5'; $GLOBALS['rate']=0.23; $GLOBALS['coupon_owner']=71; $GLOBALS['coupon_status']='publish'; $GLOBALS['code']='demo23'; $GLOBALS['rate_type']='percentage'; $GLOBALS['expiry']=null; $GLOBALS['optout']=false; $GLOBALS['notifications']=true;
function add_filter($n,$f,$p=10,$a=1){$GLOBALS['hooks'][$n][$p]=$f;}
function add_action($n,$f,$p=10,$a=1){add_filter($n,$f,$p,$a);}
function remove_filter($n,$f,$p=10){unset($GLOBALS['hooks'][$n][$p]);}
function remove_action($n,$f,$p=10){remove_filter($n,$f,$p);}
function wp_parse_url($url,$part){return parse_url($url,$part);}
function home_url($p){return 'https://'.$GLOBALS['host'].$p;}
function network_site_url($p,$scheme){return home_url('/'.$p);}
function is_wp_error($e){return $e instanceof Exception;}
function is_email($v){return filter_var($v,FILTER_VALIDATE_EMAIL);}
function get_user_by($field,$id){return $id===42?(object)['ID'=>42,'user_login'=>'demo_creator','display_name'=>'Alex & <creator>','user_email'=>'demo@example.test']:false;}
function get_user_meta($id,$k,$single){return $GLOBALS['optout'];}
function affwp_get_affiliate($id){return $id===71?(object)['user_id'=>42,'status'=>'active']:false;}
function affwp_get_affiliate_rate($id){return $GLOBALS['rate'];}
function affwp_get_affiliate_referral_url($args){return home_url('/ref/demo-slug/');}
function affwp_get_affiliate_meta($id,$k,$single){return $GLOBALS['slug'] ?? 'demo-slug';}
function affwp_get_affiliate_rate_type($id){return $GLOBALS['rate_type'];}
function affwp_email_notification_enabled($name){return $GLOBALS['notifications'];}
function get_post_meta($id,$k,$single){return $GLOBALS['coupon_owner'];}
function maybe_serialize($v){return serialize($v);}
function wp_cache_delete($k,$g){return true;}
class FakeWpdb {
    public $options='wp_options';
    function prepare($sql,...$args){if(strpos($sql,'INSERT IGNORE')===false || stripos($sql,'ON DUPLICATE')!==false)throw new Exception('unsafe claim');return $args;}
    function query($args){[$key,$value]=$args;if(isset($GLOBALS['opts'][$key]))return 0;$GLOBALS['opts'][$key]=unserialize($value);return 1;}
}
$GLOBALS['wpdb']=new FakeWpdb();
function get_option($k,$default=false){return $GLOBALS['opts'][$k]??$default;}
function add_option($k,$v,$deprecated='',$autoload=false){if(array_key_exists($k,$GLOBALS['opts']))return false; $GLOBALS['opts'][$k]=$v;return true;}
function update_option($k,$v,$autoload=false){$GLOBALS['opts'][$k]=$v;return true;}
function get_password_reset_key($u){$GLOBALS['reset_count']++;return 'SYNTHETIC-reset-key';}
function wp_mail($to,$subject,$body,$headers){$GLOBALS['mails'][]=compact('to','subject','body','headers');return $GLOBALS['send_ok'];}
class YBY_Connector_Affiliate_Bindings {
    public static function by_kol($connection,$kol){return $connection==='test-connection'&&$kol===9?['state'=>'ready','affiliate_id'=>71,'wp_user_id'=>42]:null;}
}
class YBY_Connector_Coupon_Bindings {
    public static function by_code($code){return $code==='demo23'?['state'=>'ready','connection_key'=>'test-connection','erp_kol_id'=>9,'affiliate_id'=>71,'coupon_id'=>11]:null;}
}
class WC_Coupon {
    private $id; function __construct($id){$this->id=$id;}
    function get_id(){return $this->id;} function get_code(){return $GLOBALS['code'];}
    function get_amount(){return $GLOBALS['coupon_amount'];} function get_discount_type(){return 'percent';}
    function get_status(){return $GLOBALS['coupon_status'];} function get_date_expires(){return $GLOBALS['expiry'];}
}
class Req {
    private $route; private $body; private $method;
    function __construct($route,$body,$method='POST'){$this->route='/andy-core/v1/erp/'.$route;$this->body=$body;$this->method=$method;}
    function get_route(){return $this->route;} function get_json_params(){return $this->body;}
    function get_method(){return $this->method;} function get_header($h){return 'test-connection';}
}
require __DIR__.'/../theme/beyourlover-v3/inc/affiliate-welcome-v3.php';
function check($cond,$name){if(!$cond){fwrite(STDERR,"FAIL ".$name.PHP_EOL);exit(1);}echo "PASS ".$name.PHP_EOL;}
function reset_fixture(){ $GLOBALS['opts']=[];$GLOBALS['mails']=[];$GLOBALS['reset_count']=0;$GLOBALS['send_ok']=true;$GLOBALS['coupon_owner']=71;$GLOBALS['coupon_status']='publish';$GLOBALS['code']='demo23';$GLOBALS['rate_type']='percentage';$GLOBALS['expiry']=null;$GLOBALS['optout']=false;$GLOBALS['notifications']=true; }
function provision($new=true,$success=true){
    $request=new Req('affiliates/provision',['erp_kol_id'=>9,'email'=>'demo@example.test','preferred_username'=>'demo_creator','existing_account_policy'=>'reject','requested_affiliate_status'=>'active']);
    $handler=['callback'=>['YBY_Connector','dispatch_affiliate_provision']];
    BYL_Affiliate_Welcome_V3::before(null,$handler,$request);
    check(BYL_Affiliate_Welcome_V3::suppress_approval(true)===false,'scoped native approval suppression');
    if($new)BYL_Affiliate_Welcome_V3::record_new_user(42);
    $r=['ok'=>$success,'connection_key'=>'test-connection','data'=>['created_user'=>true,'created_affiliate'=>true,'affiliate_id'=>71,'wp_user_id'=>42,'affiliate_status'=>'active','affiliate_url_slug'=>'demo-slug','affiliate_referral_url'=>home_url('/ref/demo-slug/')]];
    check(BYL_Affiliate_Welcome_V3::after($r,$handler,$request)===$r,'original connector response preserved');
    check(BYL_Affiliate_Welcome_V3::suppress_approval(true)===true,'other approval untouched after scope');
}
function coupon(){
    $request=new Req('coupons/provision',['erp_kol_id'=>9]);
    $h=['callback'=>['YBY_Connector','dispatch_coupon_provision']];
    $r=['ok'=>true,'connection_key'=>'test-connection','data'=>['affiliate_id'=>71,'coupon_id'=>11,'normalized_code'=>'demo23']];
    check(BYL_Affiliate_Welcome_V3::after($r,$h,$request)===$r,'coupon response preserved');
}
reset_fixture();provision();check(count($GLOBALS['mails'])===0,'no mail before coupon');coupon();
check(count($GLOBALS['mails'])===1,'one mail after verified coupon');
$mail=$GLOBALS['mails'][0];$body=$mail['body'];
check(strpos($body,'17.5% OFF')!==false&&strpos($body,'23%</div>')!==false,'dynamic distinct rates');
check(strpos($body,'Alex &amp; &lt;creator&gt;')!==false&&strpos($body,'demo_creator')!==false,'escaped real identity');
check(strpos($body,'https://dev.beyourlover.com/ref/demo-slug/')!==false&&strpos($body,'demo23')!==false,'linked coupon and exact referral');
check(strpos($body,'width="140"')!==false&&strpos($body,'Affiliate Program</h1>')!==false&&strpos($body,'DESIGN REVIEW')===false,'approved V3 logo/title, no review metadata');
check(strpos($body,'SYNTHETIC-reset-key')!==false&&strpos($body,'action=rp&amp;key=')!==false,'native reset link escaped');
check(strpos($body,'ALEX20')===false&&strpos($body,'alexcreator')===false&&!preg_match('/\{\{[a-z_]+\}\}/',$body),'no example or unresolved tokens');
check($mail['to']==='demo@example.test'&&in_array('Content-Type: text/html; charset=UTF-8',$mail['headers'],true),'test envelope');
coupon();check(count($GLOBALS['mails'])===1&&$GLOBALS['reset_count']===1,'coupon replay no second send/reset');
reset_fixture();provision(false);coupon();check(count($GLOBALS['mails'])===0,'historical replay not enrolled');
reset_fixture();provision(true,false);coupon();check(count($GLOBALS['mails'])===0,'failed account not enrolled');
reset_fixture();provision();$GLOBALS['coupon_owner']=999;coupon();check(count($GLOBALS['mails'])===0,'mismatched coupon blocked');$GLOBALS['coupon_owner']=71;coupon();check(count($GLOBALS['mails'])===1,'valid replay completes previously unclaimed mail');
reset_fixture();provision();$GLOBALS['coupon_status']='draft';coupon();check(count($GLOBALS['mails'])===0,'unpublished coupon blocked');
reset_fixture();provision();$GLOBALS['expiry']=new DateTimeImmutable('-1 day');coupon();check(count($GLOBALS['mails'])===0,'expired coupon blocked');
reset_fixture();provision();$GLOBALS['slug']='changed';coupon();check(count($GLOBALS['mails'])===0,'changed custom referral blocked');unset($GLOBALS['slug']);
reset_fixture();provision();$GLOBALS['rate_type']='flat';coupon();check(count($GLOBALS['mails'])===0,'unsupported rate not falsely shown as percentage');
reset_fixture();provision();$GLOBALS['optout']=true;coupon();check(count($GLOBALS['mails'])===0,'affiliate opt-out respected');
reset_fixture();provision();$GLOBALS['notifications']=false;coupon();check(count($GLOBALS['mails'])===0,'native disabled notification respected');
reset_fixture();provision();$GLOBALS['send_ok']=false;coupon();coupon();check(count($GLOBALS['mails'])===1&&$GLOBALS['opts']['byl_aff_welcome_v3_dispatch_71']['state']==='needs_review','failed or ambiguous mail not silently retried');
check(BYL_Affiliate_Welcome_V3::render(['name'=>'only'])===null,'missing fields fail closed');
check(BYL_Affiliate_Welcome_V3::render(['name'=>'x','username'=>'x','password_url'=>'https://other.example.test/r','login_url'=>home_url('/affiliate-login/'),'discount_rate'=>'20%','commission_rate'=>'20%','coupon'=>'x','referral_url'=>home_url('/ref/x/')])===null,'foreign reset URL blocked');
$GLOBALS['host']='other.example.test';check(!BYL_Affiliate_Welcome_V3::enabled(),'other sites disabled');
if(isset($argv[1]))file_put_contents($argv[1],$body);
echo "Affiliate welcome V3 isolated contract PASS\n";
