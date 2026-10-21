<?php
/** Safe offline source contract: no network, Woo database or live carts. */
declare(strict_types=1);
define('ABSPATH', __DIR__);
$GLOBALS['host'] = 'dev.beyourlover.com';
$GLOBALS['environment'] = 'staging';
$GLOBALS['enabled'] = 'yes';
$GLOBALS['hooks'] = [];
$GLOBALS['cart_items'] = [];
function home_url($path = '/') { return 'https://' . $GLOBALS['host'] . $path; }
function wp_parse_url($url, $component) { return parse_url($url, $component); }
function wp_get_environment_type() { return $GLOBALS['environment']; }
function get_option($name, $default = null) {
    if ($name === 'byl_dev_restworld_shipping_active_v1') {
        return $GLOBALS['enabled'];
    }
    if ($name === 'woocommerce_free_shipping_11_settings') {
        return ['requires'=>'min_amount','min_amount'=>'69','ignore_discounts'=>'no'];
    }
    return $default;
}
function WC() { return (object)['cart' => new TestCart()]; }
class TestCart { public function get_cart(): array { return $GLOBALS['cart_items']; } }
class WC_Shipping_Zones {
    public static function get_zone_matching_package(array $package) {
        $country = $package['destination']['country'] ?? '';
        return new TestZone(in_array($country, ['GB','FR'], true) ? 0 : 5);
    }
}
class TestZone { public function __construct(private int $id) {} public function get_id(): int { return $this->id; } }
class WC_Tax {
    public static function get_shipping_tax_rates(): array { return []; }
    public static function calc_shipping_tax($cost,$rates): array { return []; }
}
class WC_Shipping_Rate {
    private ?string $label = null;
    private array $taxes = [];
    public function __construct(private string $id, private string $method, private float $cost) {}
    public function get_id(): string { return $this->id; }
    public function get_method_id(): string { return $this->method; }
    public function get_instance_id(): int { return (int)explode(':',$this->id)[1]; }
    public function get_cost(): float { return $this->cost; }
    public function set_cost(float $cost): void { $this->cost = $cost; }
    public function set_label(string $label): void { $this->label = $label; }
    public function get_label(): ?string { return $this->label ?? null; }
    public function set_taxes(array $taxes): void { $this->taxes = $taxes; }
    public function get_taxes(): array { return $this->taxes ?? []; }
}
class TestRestResponse {
    public function __construct(private array $data) {}
    public function get_data(): array { return $this->data; }
    public function set_data(array $next): void { $this->data = $next; }
}
class TestRestRequest {
    public function __construct(private string $route) {}
    public function get_route(): string { return $this->route; }
}
function add_filter($name,$handler,$priority=10,$arity=1){$GLOBALS['hooks'][$name][]=[$handler,$priority,$arity];}
function add_action($name,$handler,$priority=10){$GLOBALS['hooks'][$name][]=[$handler,$priority];}
function byl_dev_pillow_shipping_is_pillow(array $item): bool {
    return (int)($item['product_id'] ?? 0)===38047;
}
function byl_dev_pillow_shipping_enabled(): bool { return true; }
require dirname(__DIR__) . '/theme/beyourlover-v3/inc/dev-restworld-shipping-v1.php';
$checks=0;
function check(bool $cond, string $why): void {
    global $checks;
    if (!$cond) { fwrite(STDERR, "FAIL $why\n"); exit(1); }
    $checks++;
}
function item(int $id, float $line, int $qty=1): array {
    return ['product_id'=>$id, 'line_total'=>$line, 'quantity'=>$qty];
}
function package(array $items,string $country='GB'): array {
    return ['contents'=>$items,'destination'=>['country'=>$country],
        'contents_cost'=>byl_dev_restworld_sum($items),
        'byl_dev_restworld_kind'=>'ordinary'];
}
function rates(float $flatCost=20.0): array {
    return [
        'free_shipping:11'=>new WC_Shipping_Rate('free_shipping:11','free_shipping',0.0),
        'flat_rate:3'=>new WC_Shipping_Rate('flat_rate:3','flat_rate',$flatCost),
    ];
}
check(byl_dev_restworld_active(), 'Dev staging allowlist + explicit activation');
byl_dev_restworld_boot();
check(count($GLOBALS['hooks']['woocommerce_cart_shipping_packages'] ?? [])===1,
    'packages filter registered');
check($GLOBALS['hooks']['woocommerce_package_rates'][0][1]===100010,
    'late rate filter after existing Dev PR150/152');
$specials=[9145,9153,32251];
foreach($specials as $id) check(byl_dev_restworld_special(item($id,179)),
    "only explicitly approved special $id");
check(!byl_dev_restworld_special(item(38047,59)),
    'PILLOW is separate quantity-based policy');
check(!byl_dev_restworld_special(item(37763,20)),
    'normal goods remain normal');
$source=package(['regular'=>item(37763,20),'kit'=>item(9145,179)]);
$split=byl_dev_restworld_packages([$source]);
check(count($split)===2, 'ordinary and special are separate packages');
check($split[0]['byl_dev_restworld_kind']==='ordinary','ordinary package first');
check($split[1]['byl_dev_restworld_kind']==='special30','special package second');
check(array_keys($split[1]['contents'])===['kit'],'special only contains kit');
check(array_keys($split[0]['contents'])===['regular'],'normal only contains regular');
$twospecial=byl_dev_restworld_packages([package(['a'=>item(9145,179),'b'=>item(9153,179)])]);
check(count($twospecial)===1,'same special class shares one shipment');
$mix=byl_dev_restworld_packages([package([
    'sleeve'=>item(37763,20),'special'=>item(32251,98),'pillow'=>item(38047,59)
])]);
check(count($mix)===1
    && $mix[0]['byl_dev_restworld_kind']==='unsafe_unsplit_pillow',
    'unsplit PILLOW must be marked unsafe');
check(byl_dev_restworld_rates(rates(),$mix[0])===[],
    'unsplit PILLOW cannot receive a new ordinary rate');
$realSplitInput=package(['sleeve'=>item(37763,20),'special'=>item(32251,98)]);
$realPillowInput=package(['pillow'=>item(38047,59)]);
$realPillowInput['byl_dev_pillow_kind']='pillow';
$realThreeWay=byl_dev_restworld_packages([$realSplitInput,$realPillowInput]);
check(count($realThreeWay)===3,'PR150 package output -> normal, special, pillow');
check($realThreeWay[2]['byl_dev_pillow_kind']==='pillow',
    'third package still PILLOW');
$preSplitPillow=package(['pillow'=>item(38047,59)]);
$preSplitPillow['byl_dev_pillow_kind']='pillow';
check(byl_dev_restworld_packages([$preSplitPillow])[0]===$preSplitPillow,
    'existing separate PILLOW package untouched');
$us=package(['normal'=>item(37763,20),'special'=>item(32251,98)],'US');
check(byl_dev_restworld_packages([$us])[0]===$us,
    'US Zone5 unchanged');
foreach([[20,false],[60,false],[68.99,false],[69,true],[70,true],[80,true]] as [$amount,$free]) {
    $part=package(['sku'=>item(37763,$amount)]);
    $actual=byl_dev_restworld_rates(rates(),$part);
    check(isset($actual['free_shipping:11'])===$free,
        "USD $amount free toggle");
    check(isset($actual['flat_rate:3'])!==$free,
        "USD $amount flat toggle");
}
$specialPackage=$split[1];
$specialRates=byl_dev_restworld_rates(rates(),$specialPackage);
check(count($specialRates)===1,'special has single true Woo rate');
check(!isset($specialRates['free_shipping:11']),'special never free at $179');
check($specialRates['flat_rate:3']->get_cost()===30.0,
    'special is $30 independent of configured class surcharge');
$specialRatesAfterSurcharge=byl_dev_restworld_rates(rates(50.0),$specialPackage);
check($specialRatesAfterSurcharge['flat_rate:3']->get_cost()===30.0,
    'class special stays $30 even if Woo originally calculated $50');
check($specialRates['flat_rate:3']->get_label()==='Extra $30 Shipping',
    'truthful special shipping label');
check(byl_dev_restworld_rates([], $specialPackage)===[],
    'special never invents unsupported Woo destination rate');
check(byl_dev_restworld_rates(rates(),$preSplitPillow)['flat_rate:3']->get_cost()===20.0,
    'PILLOW package left untouched; prior adapter owns $30/item');
check(byl_dev_restworld_rates(rates(),$us)['flat_rate:3']->get_cost()===20.0,
    'US zone untouched');
foreach([[60,false],[69,true]] as [$amount,$free]) {
    $p=package(['sleeve'=>item(37763,$amount)]);
    $p['byl_dev_restworld_kind']='ordinary';
    check(isset(byl_dev_restworld_rates(rates(),$p)['free_shipping:11'])===$free,
        'discounted line subtotal eligibility');
}
$promo=['active'=>true,'zone_id'=>0,'threshold'=>49,'subtotal'=>199,
    'remaining'=>0,'eligible'=>true,'unlocked'=>true,'state'=>'automatic_unlocked',
    'mode'=>'automatic','applied_code'=>'','policy_code_applied'=>false];
$items=[
    'regular'=>item(37763,60),
    'kit'=>item(9145,179),
    'pillow'=>item(38047,59)
];
$correct=byl_dev_restworld_promo_correct($promo,$items);
check($correct['threshold']===69.0,'Zone0 promo threshold corrected to $69');
check($correct['subtotal']===60.0,'only discounted ordinary $60 eligible');
check($correct['remaining']===9.0,'$9 remaining');
check($correct['unlocked']===false && $correct['eligible']===false,
    'mixed special/pillow does not unlock normal free shipping');

/**
 * Real Dev Store API evidence 2026-10-21: fixed_cart coupon $20 on a
 * mixed $80 regular + special item reported ordinary discounted line_total
 * $64 (not the script's assumed $60). The Woo line_total is authoritative
 * regardless of how Woo distributes a coupon across cart items.
 * This fixture asserts rate policy for observed ordinary $64; it does NOT
 * claim independent proof of special-product discount allocation.
 */
$allocatedLineItems=[
    'ordinary'=>item(37763,64.00),
    'special'=>item(32251,98.00),
];
$allocatedPackages=byl_dev_restworld_packages([package($allocatedLineItems)]);
check(count($allocatedPackages)===2,'discounted mixed package split');
$allocatedNormalRates=byl_dev_restworld_rates(rates(),$allocatedPackages[0]);
$allocatedSpecialRates=byl_dev_restworld_rates(rates(50.0),$allocatedPackages[1]);
check(isset($allocatedNormalRates['flat_rate:3'])
    && !isset($allocatedNormalRates['free_shipping:11']),
    'observed $64 ordinary subtotal still requires $20');
check($allocatedNormalRates['flat_rate:3']->get_cost()===20.0,
    'ordinary $64 shipping stays $20');
check($allocatedSpecialRates['flat_rate:3']->get_cost()===30.0,
    'mixed special shipment remains $30');
$allocatedPromo=byl_dev_restworld_promo_correct($promo,$allocatedLineItems);
check($allocatedPromo['subtotal']===64.0
    && $allocatedPromo['remaining']===5.0
    && $allocatedPromo['unlocked']===false,
    'mixed coupon messaging reflects discounted ordinary $64, not guessed $60');

$otherPromo=$promo;
$otherPromo['zone_id']=5;
check(byl_dev_restworld_promo_correct($otherPromo,$items)===$otherPromo,
    'other zones untouched');
$GLOBALS['cart_items']=$items;
$resp=new TestRestResponse(['extensions'=>(object)[
    'byl-shipping-promotion'=>(object)$promo,
    'other'=>(object)['ok'=>true]
], 'totals'=>['total_shipping'=>'9999']]);
byl_dev_restworld_store_response($resp,null,new TestRestRequest('/wc/store/v1/cart'));
$data=$resp->get_data();
check($data['extensions'] instanceof stdClass,'preserve Woo Store API registry shape');
check($data['extensions']->{'byl-shipping-promotion'}->subtotal===60.0,
    'rest endpoint corrected');
check($data['extensions']->other->ok===true,'unrelated extension untouched');
check($data['totals']['total_shipping']==='9999','never overwrite Woo amounts');
$checkout=new TestRestResponse(['extensions'=>(object)[
    'byl-shipping-promotion'=>(object)$promo
]]);
byl_dev_restworld_store_response($checkout,null,
    new TestRestRequest('/wc/store/v1/checkout'));
check($checkout->get_data()['extensions']->{'byl-shipping-promotion'}->threshold===49,
    'no checkout endpoint metadata spoofing');
foreach([['www.beyourlover.com','production','yes'],
    ['dev.beyourlover.com','production','yes'],
    ['dev.beyourlover.com','staging','no']
] as [$host,$env,$enabled]) {
    $GLOBALS['host']=$host; $GLOBALS['environment']=$env;
    $GLOBALS['enabled']=$enabled;
    check(!byl_dev_restworld_active(), 'never active outside explicitly enabled Dev');
    check(byl_dev_restworld_rates(rates(),package(['sku'=>item(37763,20)]))
        ['flat_rate:3']->get_cost()===20.0,'WWW/disabled rate unchanged');
}
echo "PASS Dev Rest of World exception policy: $checks offline assertions, no Woo/WWW changes\n";
