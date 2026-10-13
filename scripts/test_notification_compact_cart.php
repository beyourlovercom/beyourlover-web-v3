<?php
/** C1 Cart Dev-only gate: synthetic WP stubs, no network or Woo writes. */
declare(strict_types=1);
define('ABSPATH', '/synthetic/');
$GLOBALS['host']='dev.beyourlover.com';$GLOBALS['cart']=false;$GLOBALS['bridge']=false;
$GLOBALS['admin']=false;$GLOBALS['ajax']=false;$GLOBALS['styles']=[];$GLOBALS['hooks']=[];
function is_cart(): bool {return $GLOBALS['cart'];}
function home_url(string $s='/'): string {return 'https://'.$GLOBALS['host'].$s;}
function wp_parse_url(string $s,int $component): string|false|null{return parse_url($s,$component);}
function wp_unslash($v){return $v;}
function is_admin(): bool{return $GLOBALS['admin'];}
function wp_doing_ajax(): bool{return $GLOBALS['ajax'];}
function byl_v3_notification_cart_bridge_enabled(): bool{return $GLOBALS['bridge'];}
function add_action(string $hook,callable $callback,int $priority=10): void{$GLOBALS['hooks']['a'][$hook][]=$callback;}
function add_filter(string $hook,callable $callback,int $priority=10): void{$GLOBALS['hooks']['f'][$hook][]=$callback;}
function get_stylesheet_directory(): string{return dirname(__DIR__).'/theme/beyourlover-v3';}
function get_stylesheet_directory_uri(): string{return 'https://dev.beyourlover.com/wp-content/themes/beyourlover-v3';}
function wp_enqueue_style(string $handle,string $uri,array $depends=[],string $version=''): void{
    $GLOBALS['styles'][$handle]=['uri'=>$uri,'deps'=>$depends,'version'=>$version];
}
require dirname(__DIR__).'/theme/beyourlover-v3/inc/notification-compact-cart.php';
$ok=static function (bool $expect,string $label): void {
    if (byl_v3_compact_cart_preview_enabled()!==$expect) {fwrite(STDERR,"FAIL: $label\n");exit(1);}
};
$_SERVER['REQUEST_METHOD']='GET';$_GET=[];$GLOBALS['cart']=true;$ok(false,'default OFF');
$_GET=['byl_compact_notice_preview'=>'cart'];$ok(true,'explicit Cart GET');
foreach ($GLOBALS['hooks']['a']['wp_enqueue_scripts'] as $f){$f();}
if(count($GLOBALS['styles'])!==2)exit(2);
if(!isset($GLOBALS['styles']['byl-v3-compact-cart']))exit(3);
foreach ($GLOBALS['hooks']['f']['body_class'] as $f){$classes=$f([]);}
if($classes!==['byl-cpn-cart-preview'])exit(4);
$GLOBALS['host']='www.beyourlover.com';$ok(false,'WWW fail closed');$GLOBALS['host']='dev.beyourlover.com';
$GLOBALS['cart']=false;$ok(false,'non Cart');$GLOBALS['cart']=true;
$_GET=['byl_compact_notice_preview'=>'checkout'];$ok(false,'Checkout isolated');
$_GET=['byl_compact_notice_preview'=>['cart']];$ok(false,'array injection');
$_GET=['byl_compact_notice_preview'=>'cart'];$GLOBALS['bridge']=true;$ok(false,'N6 excluded');$GLOBALS['bridge']=false;
$_SERVER['REQUEST_METHOD']='POST';$ok(false,'POST excluded');$_SERVER['REQUEST_METHOD']='GET';
$GLOBALS['admin']=true;$ok(false,'admin excluded');$GLOBALS['admin']=false;
$GLOBALS['ajax']=true;$ok(false,'Ajax excluded');
echo "CPN_C1_CART_GATE_PASS: default OFF, Dev GET, Cart-only, N6 isolated, no Woo writes\n";
