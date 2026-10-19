<?php
/** Offline no-network Dev-only shipping regression for #149. */
declare(strict_types=1);
define('ABSPATH', __DIR__);

$GLOBALS['pillow_test_host'] = 'dev.beyourlover.com';
$GLOBALS['pillow_test_env'] = 'staging';
$GLOBALS['pillow_test_filters'] = [];
function home_url($path = '/') { return 'https://' . $GLOBALS['pillow_test_host'] . $path; }
function wp_parse_url($url, $component) { return parse_url($url, $component); }
function wp_get_environment_type() { return $GLOBALS['pillow_test_env']; }
function WC() { return (object) []; }
function add_action($hook, $callback, $priority = 10) { $GLOBALS['pillow_test_filters'][$hook] = $callback; }
function add_filter($hook, $callback, $priority = 10, $accepted = 1) {
    $GLOBALS['pillow_test_filters'][$hook] = [$callback, $priority, $accepted];
}
function get_option($key, $default = []) {
    return $key === 'woocommerce_free_shipping_9_settings'
        ? ['requires' => 'min_amount', 'min_amount' => '69', 'ignore_discounts'=>'no'] : $default;
}
class WC_Tax {
    public static function get_shipping_tax_rates() { return []; }
    public static function calc_shipping_tax($cost, $rates) { return []; }
}
class WC_Shipping_Rate {
    private string $id;
    private string $method;
    private string $label;
    private float $cost;
    private int $instance;
    private array $taxes;
    public function __construct($id, $label, $cost = 0, $taxes = [], $method_id = '', $instance = 0) {
        $this->id = $id; $this->label = $label; $this->cost = (float)$cost;
        $this->taxes = $taxes; $this->method = $method_id; $this->instance = (int)$instance;
    }
    public function get_id() {return $this->id;}
    public function get_method_id() {return $this->method;}
    public function get_instance_id() {return $this->instance;}
    public function get_cost() {return $this->cost;}
    public function get_label() {return $this->label;}
    public function get_taxes() {return $this->taxes;}
    public function set_cost($v) {$this->cost = (float)$v;}
    public function set_label($v) {$this->label = (string)$v;}
    public function set_taxes($v) {$this->taxes = $v;}
}
require dirname(__DIR__) . '/theme/beyourlover-v3/inc/dev-pillow-oversized-shipping.php';

$tests=0;
function check_shipping($condition, $label) {
    global $tests;
    if (!$condition) {fwrite(STDERR, "FAIL Dev pillow shipping: $label\n"); exit(1);}
    $tests++;
}
$pillow = ['product_id'=>38047,'variation_id'=>38165,'quantity'=>1,'line_total'=>59];
$normal = ['product_id'=>25447,'variation_id'=>0,'quantity'=>1,'line_total'=>96];
$destination=['country'=>'US','state'=>'CA','postcode'=>''];
$build=static fn(array $contents):array =>
    [['contents'=>$contents,'contents_cost'=>array_sum(array_column($contents,'line_total')),'destination'=>$destination]];
$rflat=static fn()=> new WC_Shipping_Rate('flat_rate:10','Flat rate',10,[],'flat_rate',10);
$rfree=static fn()=> new WC_Shipping_Rate('free_shipping:9','Free shipping',0,[],'free_shipping',9);
$rates=static fn()=> ['flat_rate:10'=>$rflat(),'free_shipping:9'=>$rfree()];

check_shipping(byl_dev_pillow_shipping_enabled(), 'active on exact Dev+staging');
byl_dev_pillow_shipping_boot();
check_shipping(isset($GLOBALS['pillow_test_filters']['woocommerce_cart_shipping_packages']), 'package hook');
check_shipping($GLOBALS['pillow_test_filters']['woocommerce_package_rates'][1]===99999, 'rate final priority');
check_shipping(byl_dev_pillow_shipping_qty([$pillow])===1, 'parent variation detected');

foreach ([1,2,3] as $q) {
    $item=$pillow;
    $item['quantity']=$q;
    $item['line_total']=$q*59;
    $p=byl_dev_pillow_shipping_packages($build(['pillow'=>$item]));
    check_shipping(count($p)===1 && $p[0]['byl_dev_pillow_kind']==='pillow', 'pillow-only package');
    check_shipping($p[0]['byl_dev_pillow_quantity']===$q, 'quantity '.$q);
    $result=byl_dev_pillow_shipping_rates($rates(), $p[0]);
    check_shipping(count($result)===1,'pillow-only one rate');
    $rate=reset($result);
    check_shipping($rate->get_cost()===30.0*$q, '$30 per pillow qty '.$q);
    check_shipping($rate->get_label()==='Oversized Pillow Shipping', 'no misleading free method');
    check_shipping($rate->get_method_id()==='flat_rate', 'Woo flat method retained');
}
$mixed=byl_dev_pillow_shipping_packages($build(['pillow'=>$pillow,'normal'=>$normal]));
check_shipping(count($mixed)===2, 'mixed split into two packages');
check_shipping($mixed[0]['byl_dev_pillow_kind']==='ordinary', 'normal first');
check_shipping($mixed[1]['byl_dev_pillow_kind']==='pillow', 'pillow second');
check_shipping(byl_dev_pillow_shipping_items_total($mixed[0]['contents'])===96.0, 'normal subtotal excludes pillow');
check_shipping(byl_dev_pillow_shipping_items_total($mixed[1]['contents'])===59.0, 'pillow subtotal');
$normalRates=byl_dev_pillow_shipping_rates($rates(),$mixed[0]);
check_shipping(isset($normalRates['free_shipping:9']), 'normal $96 retains $69 free rule');
$pillowRates=byl_dev_pillow_shipping_rates($rates(),$mixed[1]);
check_shipping(reset($pillowRates)->get_cost()===30.0, 'mixed pillow still $30');
$cheap=['product_id'=>25447,'quantity'=>1,'line_total'=>20];
$under=byl_dev_pillow_shipping_packages($build(['pillow'=>$pillow,'normal'=>$cheap]));
$unlocked=byl_dev_pillow_shipping_rates($rates(),$under[0]);
check_shipping(!isset($unlocked['free_shipping:9']), 'pillow $59 must not unlock normal $20');
check_shipping(isset($unlocked['flat_rate:10']), 'normal under threshold retains $10');
$discounted=['product_id'=>25447,'quantity'=>1,'line_total'=>68];
$cut=byl_dev_pillow_shipping_packages($build(['pillow'=>$pillow,'normal'=>$discounted]));
$afterCoupon=byl_dev_pillow_shipping_rates($rates(),$cut[0]);
check_shipping(!isset($afterCoupon['free_shipping:9']), 'after-coupon normal $68 loses $69 free');
$alone=byl_dev_pillow_shipping_packages($build(['normal'=>$normal]));
check_shipping(count($alone)===1 && !isset($alone[0]['byl_dev_pillow_kind']), 'ordinary-only cart unchanged');
$fallback=byl_dev_pillow_shipping_rates(['free_shipping:9'=>$rfree()],$mixed[1]);
check_shipping(count($fallback)===1 && reset($fallback)->get_cost()===30.0, 'no flat method still charges pillow');
check_shipping(reset($fallback)->get_method_id()==='byl_dev_pillow_oversized','fallback dedicated Woo rate');
$initial=byl_dev_pillow_shipping_packages($build(['pillow'=>$pillow,'normal'=>$normal]));
foreach ([['www.beyourlover.com','staging'],['dev.beyourlover.com','production'],['www.beyourlover.com','production']] as $scope) {
    $GLOBALS['pillow_test_host']=$scope[0];$GLOBALS['pillow_test_env']=$scope[1];
    check_shipping(!byl_dev_pillow_shipping_enabled(),'non-Dev scope OFF');
    check_shipping(byl_dev_pillow_shipping_packages($build(['pillow'=>$pillow]))===$build(['pillow'=>$pillow]),'no split outside Dev');
    check_shipping(count(byl_dev_pillow_shipping_rates($rates(),$mixed[1]))===2,'no rate rewrite outside Dev');
}
$src=file_get_contents(dirname(__DIR__) . '/theme/beyourlover-v3/inc/dev-pillow-oversized-shipping.php');
check_shipping(is_string($src) && !str_contains($src,'update_option(') && !str_contains($src,'wp_insert_post(')
    && !str_contains($src,'wp_mail(') && !str_contains($src,'woocommerce_checkout_create_order'),
    'zero writes/payment/transaction hooks');
echo "PASS BYL Dev-only Pillow Shipping V1: $tests offline assertions, WWW=OFF, no Woo/DB writes\n";
