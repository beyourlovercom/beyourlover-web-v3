<?php
/**
 * R13 isolated safety gate: exact admin/TEST/fixture/transport constraints.
 * Mock-only: no network, WordPress DB, Woo orders, payment or mail side effects.
 */
declare(strict_types=1);
define('ABSPATH', '/isolated-r13/');
$GLOBALS['hooks'] = [];
$GLOBALS['gate'] = [
    'environment'=>'staging', 'home'=>'https://dev.beyourlover.com',
    'ssl'=>true, 'logged_in'=>true, 'admin'=>true,
    'armed'=>true, 'payment_ready'=>true, 'base'=>'USD',
    'currency'=>'EUR', 'orders'=>0,
];
function add_action($name,$callback,$priority=10,$argc=1): void {}
function add_filter($name,$callback,$priority=10,$argc=1): void {
    $GLOBALS['hooks'][$name][] = ['callback'=>$callback,'priority'=>$priority];
}
function wp_get_environment_type(): string { return $GLOBALS['gate']['environment']; }
function home_url($path=''): string { return $GLOBALS['gate']['home'].$path; }
function is_ssl(): bool { return $GLOBALS['gate']['ssl']; }
function is_user_logged_in(): bool { return $GLOBALS['gate']['logged_in']; }
function current_user_can($cap): bool { return $GLOBALS['gate']['admin']; }
function get_option($name,$fallback=null) {
    return $name==='woocommerce_currency' ? $GLOBALS['gate']['base'] : $fallback;
}
function get_woocommerce_currency(): string { return $GLOBALS['gate']['currency']; }
function get_post_meta($id,$key,$single=false): string {
    return in_array($id,[40026,40027,40028,40029],true)
        && $key==='_aicc_marketing_uat_fixture' ? 'yes' : '';
}
function wc_get_orders($args): array { return array_fill(0,$GLOBALS['gate']['orders'],17); }
class WP_REST_Request {
    public function __construct(private string $method,private string $route,private array $params=[]) {}
    public function get_route(): string { return $this->route; }
    public function get_method(): string { return $this->method; }
    public function get_param(string $key) { return $this->params[$key]??null; }
}
class WP_Error {
    public function __construct(private string $code,private string $message,private array $data) {}
    public function get_error_code(): string { return $this->code; }
    public function get_error_data(): array { return $this->data; }
}
class Andy_Commerce_Currency_R13_Dev_Canary {
    public static function armed(): bool { return $GLOBALS['gate']['armed']; }
    public static function payment_ready(): bool { return $GLOBALS['gate']['payment_ready']; }
}
class WC_Product_Simple {
    public function is_virtual(): bool { return false; }
    public function get_catalog_visibility(): string { return 'hidden'; }
}
class WC_Cart {
    public array $items;
    public function __construct() {
        $this->items=[['product_id'=>40026,'variation_id'=>0,'quantity'=>2,
            'data'=>new WC_Product_Simple()]];
    }
    public function get_cart(): array { return $this->items; }
    public function get_cart_contents_count(): int {
        return array_sum(array_column($this->items,'quantity'));
    }
    public function get_applied_coupons(): array { return []; }
    public function get_subtotal(): float { return 80.0; }
}
class R13_Session {
    public string $shipping='flat_rate:10';
    public function get($key,$fallback=[]) { return $key==='chosen_shipping_methods'?[$this->shipping]:$fallback; }
}
class R13_WC {
    public WC_Cart $cart;
    public R13_Session $session;
    public function __construct() { $this->cart=new WC_Cart();$this->session=new R13_Session(); }
}
$GLOBALS['wc'] = new R13_WC();
function WC(): R13_WC { return $GLOBALS['wc']; }
require dirname(__DIR__,3).'/dev/mu-plugins/byl-dev-safety.php';
$gateFilters = array_values(array_filter($GLOBALS['hooks']['rest_pre_dispatch']??[],
    static fn($x)=>$x['priority']===99));
if(count($gateFilters)!==1)throw new RuntimeException('Expected one REST Checkout guard at priority 99');
$dispatch=$gateFilters[0]['callback'];
$post = fn(array $p=[])=>new WP_REST_Request('POST','/wc/store/v1/checkout',$p);
$get = fn()=>new WP_REST_Request('GET','/wc/store/v1/checkout');
$params=['billing_address'=>['email'=>'aicc-r13-uat@example.invalid'],
    'payment_method'=>'stripe'];
$count=0;
function ok(bool $value,string $description): void {
    global $count;
    if(!$value)throw new RuntimeException('R13_DENY_OR_ALLOW_FAILURE: '.$description);
    $count++;
}
function rejected($result): bool {
    return $result instanceof WP_Error
        && $result->get_error_code()==='byl_dev_sandbox_checkout_closed'
        && ($result->get_error_data()['status']??null)===403;
}
ok(byl_dev_r13_stripe_checkout($post($params)),'valid admin UAT POST allowed');
ok($dispatch(null,null,$post($params))===null,'valid POST passes to native Woo validation');
ok($dispatch(null,null,$get())===null,'valid GET allowed');
$gatewayFilters=array_values(array_filter($GLOBALS['hooks']['woocommerce_available_payment_gateways']??[],
    static fn($x)=>$x['priority']===99));
ok(count($gatewayFilters)===1,'only one payment gateway filter');
$methods=$gatewayFilters[0]['callback'](['stripe'=>'test-rail','cod'=>'cod-rail']);
ok($methods===['stripe'=>'test-rail'],'only Stripe rail for armed admin');
foreach([
    'environment'=>'production','home'=>'https://www.beyourlover.com',
    'ssl'=>false,'logged_in'=>false,'admin'=>false,
    'armed'=>false,'payment_ready'=>false,'base'=>'EUR',
    'currency'=>'USD',
] as $key=>$bad){
    $old=$GLOBALS['gate'][$key];$GLOBALS['gate'][$key]=$bad;
    ok(rejected($dispatch(null,null,$post($params))),'deny: '.$key);
    $GLOBALS['gate'][$key]=$old;
}
$old=$GLOBALS['wc']->cart->items;
$GLOBALS['wc']->cart->items[0]['product_id']=99999;
ok(rejected($dispatch(null,null,$post($params))),'reject nonfixture SKU');
$GLOBALS['wc']->cart->items=$old;
$GLOBALS['wc']->cart->items[0]['quantity']=9;
ok(rejected($dispatch(null,null,$post($params))),'reject over eight units');
$GLOBALS['wc']->cart->items=$old;
$bad=$params;$bad['billing_address']['email']='external@example.com';
ok(rejected($dispatch(null,null,$post($bad))),'reject wrong test email');
$bad=$params;$bad['payment_method']='cod';
ok(rejected($dispatch(null,null,$post($bad))),'reject wrong payment rail');
$GLOBALS['wc']->session->shipping='free_shipping:9';
ok(rejected($dispatch(null,null,$post($params))),'reject non-allowed shipping');
$GLOBALS['wc']->session->shipping='flat_rate:10';
$GLOBALS['gate']['orders']=2;
ok(rejected($dispatch(null,null,$post($params))),'reject third R13 order');
$GLOBALS['gate']['orders']=0;
ok($dispatch(null,null,new WP_REST_Request('OPTIONS','/wc/store/v1/checkout'))===null,
    'OPTIONS stays harmless and available');
echo 'R13_SCOPED_DEV_CHECKOUT_SECURITY=PASS '.$count.' mock-only assertions'.PHP_EOL;
