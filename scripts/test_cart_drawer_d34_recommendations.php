<?php
/** Cart D3.4 recommendation selection safety smoke. No WordPress bootstrap, DB, network, orders or writes. */
declare(strict_types=1);
define('ABSPATH', '/dev-test/');
define('DAY_IN_SECONDS', 86400);
$GLOBALS['hooks'] = [];
$GLOBALS['products'] = [];
function add_action($hook,$callback,$priority=10): void { $GLOBALS['hooks'][$hook][] = $callback; }
function home_url($path='/'): string { return 'https://dev.beyourlover.com'.$path; }
function wp_parse_url($url,$part) { return parse_url($url,$part); }
function wp_unslash($str) { return stripslashes($str); }
function wp_date($format,$time): string { return gmdate($format,$time); }
function is_singular($type): bool { return $GLOBALS['product_page'] ?? false; }
function get_queried_object_id(): int { return (int)($GLOBALS['product_id']??0); }
function is_active_widget(...$args): bool { return (bool)($GLOBALS['widget_active']??false); }
function wc_setcookie($name,$val): void { $GLOBALS['cookie_writes'][]=[$name,$val]; }
function nocache_headers(): void { $GLOBALS['nocache_calls']=($GLOBALS['nocache_calls']??0)+1; }
function WC() { return new stdClass(); }
class WC_Product {
    public function __construct(public int $id,public string $type='simple',public string $status='publish',public bool $stock=true,public bool $virtual=false,public bool $purchasable=true) {}
    public function get_type() {return $this->type;}
    public function get_status() {return $this->status;}
    public function is_in_stock() {return $this->stock;}
    public function is_virtual() {return $this->virtual;}
    public function is_purchasable() {return $this->purchasable;}
    public function get_id() {return $this->id;}
}
function wc_get_product($id) {return $GLOBALS['products'][$id]??false;}
class MockWPDB {
    public string $prefix='wp_';
    public string $sql='';
    public string $cutoff='';
    public array $ids=[];
    public function prepare($sql,$cutoff){$this->sql=$sql;$this->cutoff=$cutoff;return str_replace('%s',"'".$cutoff."'",$sql);}
    public function get_col($sql) { $this->sql=$sql;return $this->ids; }
}
$wpdb=new MockWPDB();
require dirname(__DIR__).'/theme/beyourlover-v3/inc/cart-drawer.php';
function check($ok,string $description): void {if(!$ok){fwrite(STDERR,"FAIL: $description\n");exit(1);}}
check(byl_v3_cart_drawer_recent_ids('1|2|2|9|0|-3|abc|14')===[14,9,2,1],'cookie strict newest first and dedupe');
check(count(byl_v3_cart_drawer_recent_ids(implode('|',range(1,30))))===15,'cap cookie to 15 products');
$GLOBALS['products']=[
  10=>new WC_Product(10),20=>new WC_Product(20,'variable'),
  30=>new WC_Product(30),40=>new WC_Product(40),
  50=>new WC_Product(50,'simple','private'),
  60=>new WC_Product(60,'simple','publish',false),
  70=>new WC_Product(70,'simple','publish',true,true),
  80=>new WC_Product(80,'grouped')
];
$wpdb->ids=[40,30,20,10,50,60,70,80];
$_COOKIE['woocommerce_recently_viewed']='10|20|30';
check(array_map(fn($p)=>$p->get_id(),byl_v3_cart_drawer_recommendations())===[30,20,10],'exact recent view precedence');
$_COOKIE['woocommerce_recently_viewed']='10|60|50';
check(array_map(fn($p)=>$p->get_id(),byl_v3_cart_drawer_recommendations())===[10,40,30],'ineligible history skipped, top 30d fills');
unset($_COOKIE['woocommerce_recently_viewed']);
check(array_map(fn($p)=>$p->get_id(),byl_v3_cart_drawer_recommendations())===[40,30,20],'no history uses monthly paid units rank');
check(str_contains($wpdb->sql,'SUM(item.product_qty)') && str_contains($wpdb->sql,'JOIN wp_wc_order_stats')
   && str_contains($wpdb->sql,"wc-completed") && str_contains($wpdb->sql,"wc-processing")
   && str_contains($wpdb->sql,'LIMIT 50') && str_contains($wpdb->sql,'item.date_created >='),
   'Woo paid order lookup only and rolling 30 day query');
check(!str_contains(strtolower($wpdb->sql),'update ') && !str_contains(strtolower($wpdb->sql),'insert '),'sales lookup read-only');
check(abs(strtotime($wpdb->cutoff)-(time()-30*86400))<3,'rolling exactly 30 days');
$GLOBALS['product_page']=true;$GLOBALS['product_id']=20;
$GLOBALS['cookie_writes']=[];
foreach($GLOBALS['hooks']['template_redirect'] as $hook){$hook();}
check(count($GLOBALS['cookie_writes'])===1 && $GLOBALS['cookie_writes'][0]===['woocommerce_recently_viewed','20'],
    'Dev product view session cookie in Woo format');
$GLOBALS['widget_active']=true;
foreach($GLOBALS['hooks']['template_redirect'] as $hook){$hook();}
check(count($GLOBALS['cookie_writes'])===1,'native Woo active widget avoids duplicate tracking');
$GLOBALS['product_page']=false;
$_GET['byl_cart_drawer_preview']='1';
foreach($GLOBALS['hooks']['template_redirect'] as $hook){$hook();}
check(($GLOBALS['nocache_calls']??0)===1,'personalized preview sends no-cache headers');
echo "PASS D3.4 recent viewed 3, rolling 30-day Woo paid units, valid variable links, Dev-only session tracker and privacy/cache gates\n";
