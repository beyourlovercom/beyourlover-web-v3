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
class DevPillowCartMock {
    public function get_cart(): array { return (array) ($GLOBALS['pillow_test_cart_items'] ?? []); }
}
function WC() { return (object) ['cart' => new DevPillowCartMock()]; }
class DevPillowRestResponse {
    private array $data;
    public function __construct(array $data) {$this->data=$data;}
    public function get_data(): array {return $this->data;}
    public function set_data(array $d): void {$this->data=$d;}
}
class DevPillowRestRequest {
    public function __construct(private string $route) {}
    public function get_route(): string {return $this->route;}
}
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
$unsupported=byl_dev_pillow_shipping_rates([], $mixed[1]);
check_shipping($unsupported === [], 'no rates in shipping zone stays unavailable — never auto-enable unsupported countries');
$fallback=byl_dev_pillow_shipping_rates(['free_shipping:9'=>$rfree()],$mixed[1]);
check_shipping(count($fallback)===1 && reset($fallback)->get_cost()===30.0, 'no flat method still charges pillow');
check_shipping(reset($fallback)->get_method_id()==='byl_dev_pillow_oversized','fallback dedicated Woo rate');
$promoTemplate=[
    'active'=>true,'mode'=>'automatic','policy_code'=>'byl49',
    'zone_id'=>5,'threshold'=>69.0,'subtotal'=>79.0,'remaining'=>0.0,
    'eligible'=>true,'unlocked'=>true,'applied_code'=>'','has_code'=>false,
    'policy_code_applied'=>false,'state'=>'automatic_unlocked',
];
$regularTwenty = ['pillow'=>$pillow, 'ordinary'=>$cheap];
$fixed=byl_dev_pillow_shipping_promo_correct($promoTemplate,$regularTwenty);
check_shipping($fixed['subtotal']===20.0, 'Store API eligible basis ordinary subtotal $20 only');
check_shipping($fixed['remaining']===49.0, 'shipping remaining is $49');
check_shipping($fixed['eligible']===false && $fixed['unlocked']===false, 'no false unlock for $79 mixed cart');
check_shipping($fixed['state']==='below_threshold', 'correct below-threshold state');
$normalNinetySix=byl_dev_pillow_shipping_promo_correct($promoTemplate,['pillow'=>$pillow,'normal'=>$normal]);
check_shipping($normalNinetySix['subtotal']===96.0 && $normalNinetySix['unlocked']===true, 'normal $96 genuinely unlocks ordinary package');
$pillowOnly=byl_dev_pillow_shipping_promo_correct($promoTemplate,['pillow'=>$pillow]);
check_shipping($pillowOnly['subtotal']===0.0 && $pillowOnly['unlocked']===false, 'pillow alone never unlocks regular free');
check_shipping(byl_dev_pillow_shipping_promo_correct($promoTemplate,['normal'=>$normal])===$promoTemplate,'normal-only legacy extension unchanged');
$discountPromo=byl_dev_pillow_shipping_promo_correct($promoTemplate,['pillow'=>$pillow,'normal'=>$discounted]);
check_shipping($discountPromo['subtotal']===68.0 && $discountPromo['eligible']===false,'discounted normal $68 below $69');
$codeRequired=$promoTemplate;
$codeRequired['mode']='require_code';$codeRequired['policy_code_applied']=false;
$codePromo=byl_dev_pillow_shipping_promo_correct($codeRequired,['pillow'=>$pillow,'normal'=>$normal]);
check_shipping($codePromo['eligible']===true && $codePromo['unlocked']===false,'coupon required mode remains code gated');
$inactive=$promoTemplate; $inactive['active']=false;
check_shipping(byl_dev_pillow_shipping_promo_correct($inactive,$regularTwenty)===$inactive,'inactive promo untouched');
$GLOBALS['pillow_test_cart_items']=$regularTwenty;
$rest=new DevPillowRestResponse(['extensions'=>['byl-shipping-promotion'=>$promoTemplate],'totals'=>['total_price'=>'11900']]);
$check=byl_dev_pillow_shipping_promo_rest_response($rest,null,new DevPillowRestRequest('/wc/store/v1/cart'));
check_shipping($rest===$check && $check->get_data()['extensions']['byl-shipping-promotion']['subtotal']===20.0,'REST response correct on cart route');
check_shipping($check->get_data()['totals']['total_price']==='11900','REST corrector never changes actual Woo totals');
// Real Woo Store API nests extensions and namespace payloads as stdClass.
$restObject=new DevPillowRestResponse([
    'extensions'=>(object) [
        'byl-shipping-promotion'=>(object) $promoTemplate,
        'another-extension'=>(object) ['untouched'=>true],
    ],
    'totals'=>['total_price'=>'11900'],
]);
$correctedObject=byl_dev_pillow_shipping_promo_rest_response(
    $restObject,null,new DevPillowRestRequest('/wc/store/v1/cart')
);
$realData=$correctedObject->get_data();
check_shipping($realData['extensions'] instanceof stdClass, 'Woo top nested extensions stays stdClass');
check_shipping($realData['extensions']->{'byl-shipping-promotion'} instanceof stdClass, 'Woo promotion extension stays stdClass');
check_shipping($realData['extensions']->{'byl-shipping-promotion'}->subtotal===20.0, 'stdClass payload corrects $79 mixed cart to $20 ordinary');
check_shipping($realData['extensions']->{'byl-shipping-promotion'}->remaining===49.0, 'stdClass remaining $49');
check_shipping($realData['extensions']->{'byl-shipping-promotion'}->unlocked===false, 'stdClass no false free unlock');
check_shipping($realData['extensions']->{'another-extension'}->untouched===true, 'other Woo Store API extensions untouched');
check_shipping($realData['totals']['total_price']==='11900', 'actual money still untouched for stdClass registry');
$GLOBALS['pillow_test_cart_items'] = ['normal'=>$normal];
$normalObject=new DevPillowRestResponse([
    'extensions'=>(object)['byl-shipping-promotion'=>(object)$promoTemplate]
]);
byl_dev_pillow_shipping_promo_rest_response($normalObject,null,new DevPillowRestRequest('/wc/store/v1/cart'));
check_shipping($normalObject->get_data()['extensions']->{'byl-shipping-promotion'}->subtotal===79.0,
    'ordinary-only response remains untouched by PILLOW-scoped correction');
$GLOBALS['pillow_test_cart_items']=$regularTwenty;
$otherRoute=new DevPillowRestResponse(['extensions'=>['byl-shipping-promotion'=>$promoTemplate]]);
byl_dev_pillow_shipping_promo_rest_response($otherRoute,null,new DevPillowRestRequest('/wc/store/v1/checkout'));
check_shipping($otherRoute->get_data()['extensions']['byl-shipping-promotion']===$promoTemplate,'never mutate Checkout REST response');
check_shipping(isset($GLOBALS['pillow_test_filters']['rest_post_dispatch'])
    && $GLOBALS['pillow_test_filters']['rest_post_dispatch'][1]===9999,'REST hook ordered after extension registration');
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
$checkoutSource = file_get_contents(dirname(__DIR__) . '/theme/beyourlover-v3/inc/checkout.php');
$checkoutJsSource = file_get_contents(dirname(__DIR__) . '/theme/beyourlover-v3/assets/js/checkout.js');
check_shipping(is_string($checkoutSource) && str_contains($checkoutSource, "byl_dev_pillow_shipping_enabled()")
    && str_contains($checkoutSource, "'byl_dev_pillow_kind'")
    && str_contains($checkoutSource, "byl-dev-pillow-package-source"),
    'Dev only native Checkout suppresses fake pillow free-rate choice');
// PR #154 Owner PASS supersedes the older PILLOW-specific checkout banner.
 // The Woo marker still guards PHP's live rate/source; the UI now links to the
 // single full country/exception policy, while Woo amounts remain authoritative.
check_shipping(is_string($checkoutJsSource)
    && str_contains($checkoutJsSource, 'Free shipping from $69 (special items excluded)')
    && str_contains($checkoutJsSource, "policyLink.href = '/free-shipping/'")
    && str_contains($checkoutJsSource, 'byl-promo-code'),
    'PILLOW keeps Woo shipment authority while Checkout follows approved single policy link and coupons');
echo "PASS BYL Dev-only Pillow Shipping V1: $tests offline assertions, WWW=OFF, no Woo/DB writes\n";
