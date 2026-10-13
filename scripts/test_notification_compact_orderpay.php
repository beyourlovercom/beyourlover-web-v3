<?php
/** C3 independent route and enqueue checks, no WordPress or order load. */
declare(strict_types=1);
define('ABSPATH','/test/');
$GLOBALS['host']='dev.beyourlover.com';
$GLOBALS['pay']=$GLOBALS['received']=$GLOBALS['admin']=$GLOBALS['ajax']=false;
$GLOBALS['hooks']=[];$GLOBALS['styles']=[];
function is_checkout_pay_page(): bool { return $GLOBALS['pay']; }
function is_order_received_page(): bool { return $GLOBALS['received']; }
function home_url(string $p='/'): string { return 'https://'.$GLOBALS['host'].$p; }
function wp_parse_url(string $u,int $component): string|false|null {return parse_url($u,$component);}
function wp_unslash($x) { return $x; }
function is_admin(): bool { return $GLOBALS['admin']; }
function wp_doing_ajax(): bool { return $GLOBALS['ajax']; }
function add_action(string $k,callable $fn,int $priority=10): void { $GLOBALS['hooks']['actions'][$k][]=$fn; }
function add_filter(string $k,callable $fn,int $priority=10): void { $GLOBALS['hooks']['filters'][$k][]=$fn; }
function get_stylesheet_directory(): string { return dirname(__DIR__).'/theme/beyourlover-v3'; }
function get_stylesheet_directory_uri(): string { return 'https://dev.beyourlover.com/wp-content/themes/beyourlover-v3'; }
function wp_enqueue_style(string $h,string $u,array $deps=[],string $v=''): void { $GLOBALS['styles'][$h]=[$u,$deps,$v]; }
require dirname(__DIR__).'/theme/beyourlover-v3/inc/notification-compact-orderpay.php';
$reset=static function(): void {
 $GLOBALS['host']='dev.beyourlover.com';$GLOBALS['pay']=true;
 $GLOBALS['received']=$GLOBALS['admin']=$GLOBALS['ajax']=false;
 $_SERVER['REQUEST_METHOD']='GET';$_GET=[];
};
$check=static function(bool $expected,string $label): void {
 if (byl_v3_compact_orderpay_preview_enabled()!==$expected) {
  fwrite(STDERR,"FAIL ".$label."\n");exit(1);
 }
};
$reset();$check(false,'default OFF');
$_GET=['byl_compact_notice_preview'=>'orderpay'];$check(true,'Dev GET exact route');
foreach($GLOBALS['hooks']['actions']['wp_enqueue_scripts'] as $fn){$fn();}
if(count($GLOBALS['styles'])!==2)exit(2);
if(!in_array('byl-v3-order-pay',$GLOBALS['styles']['byl-v3-compact-orderpay'][1],true))exit(3);
foreach($GLOBALS['hooks']['filters']['body_class'] as $fn){$classes=$fn([]);}
if($classes!==['byl-cpn-orderpay-preview'])exit(4);
$reset();$GLOBALS['pay']=false;$_GET=['byl_compact_notice_preview'=>'orderpay'];$check(false,'other route');
$reset();$GLOBALS['received']=true;$_GET=['byl_compact_notice_preview'=>'orderpay'];$check(false,'order received');
$reset();$_GET=['byl_compact_notice_preview'=>'checkout'];$check(false,'checkout isolated');
$reset();$_GET=['byl_compact_notice_preview'=>['orderpay']];$check(false,'array injection');
$reset();$_GET=['byl_compact_notice_preview'=>'orderpay'];$GLOBALS['host']='www.beyourlover.com';$check(false,'WWW blocked');
$reset();$_GET=['byl_compact_notice_preview'=>'orderpay'];$_SERVER['REQUEST_METHOD']='POST';$check(false,'POST blocked');
$reset();$_GET=['byl_compact_notice_preview'=>'orderpay'];$GLOBALS['admin']=true;$check(false,'admin blocked');
$reset();$_GET=['byl_compact_notice_preview'=>'orderpay'];$GLOBALS['ajax']=true;$check(false,'ajax blocked');
echo "CPN_C3_ORDERPAY_GATE_PASS\n";
