<?php
/** No-network, no-WordPress, no-order test of an UNWIRED production candidate. */
declare(strict_types=1);
define('ABSPATH', __DIR__);
$GLOBALS['site'] = 'www.beyourlover.com';
$GLOBALS['environment'] = 'production';
$GLOBALS['currency'] = 'USD';
$GLOBALS['options'] = ['byl_www_shipping_v1_enabled' => 'no'];
$GLOBALS['hooks'] = [];
$GLOBALS['assertions'] = 0;
$GLOBALS['coupons'] = [];

function check_prod(bool $ok, string $name): void {
    ++$GLOBALS['assertions'];
    if (!$ok) {
        fwrite(STDERR, "FAIL prod-shipping candidate: $name\n");
        exit(2);
    }
}
function home_url(string $path = '/'): string {
    return 'https://' . $GLOBALS['site'] . $path;
}
function wp_parse_url(string $url, int $component) {
    return parse_url($url, $component);
}
function wp_get_environment_type(): string { return $GLOBALS['environment']; }
function get_option(string $key, $default = false) {
    return $GLOBALS['options'][$key] ?? $default;
}
function get_woocommerce_currency(): string { return $GLOBALS['currency']; }
function add_filter(string $name, $callable, int $priority, int $args): void {
    $GLOBALS['hooks'][$name] = [$callable, $priority, $args];
}
class ShippingZoneStub {
    public function __construct(private int $id) {}
    public function get_id(): int { return $this->id; }
    public function get_shipping_methods(bool $enabledOnly = true): array {
        return $GLOBALS['zoneMethods'][$this->id] ?? [];
    }
}
class WC_Shipping_Zones {
    public static function get_zone(int $id): ShippingZoneStub {
        return new ShippingZoneStub($id);
    }
    public static function get_zone_matching_package(array $pkg): ShippingZoneStub {
        return new ShippingZoneStub((int)($pkg['destination']['zone'] ?? -1));
    }
}
class WC_Shipping_Rate {
    private array $taxes = [];
    private string $methodId;
    public function __construct(
        private string $id,
        private string $label,
        private float $cost,
        array $taxes = [],
        string $methodId = '',
        private int $instanceId = 0,
        private string $taxStatus = 'taxable'
    ) {
        $this->methodId = $methodId !== '' ? $methodId : $label;
        $this->taxes = $taxes;
    }
    public function get_id(): string { return $this->id; }
    public function get_method_id(): string { return $this->methodId; }
    public function get_instance_id(): int { return $this->instanceId; }
    public function get_cost(): float { return $this->cost; }
    public function get_label(): string { return $this->label; }
    public function get_taxes(): array { return $this->taxes; }
    public function set_label(string $label): void { $this->label = $label; }
    public function set_cost(float $cost): void { $this->cost = $cost; }
    public function set_taxes(array $taxes): void { $this->taxes = $taxes; }
}
class ShippingMethodStub {
    public string $id;
    public function __construct(string $name, private int $instance) {
        $this->id = $name;
    }
    public function get_instance_id(): int { return $this->instance; }
}
class WC_Tax {
    public static function get_shipping_tax_rates(): array { return []; }
    public static function calc_shipping_tax(float $cost, array $rates): array {
        return $GLOBALS['taxReturn'] ?? [];
    }
}
class ProdShippingCartStub {
    public function get_applied_coupons(): array { return $GLOBALS['coupons']; }
}
function WC(): object { return (object)['cart'=>new ProdShippingCartStub()]; }

require dirname(__DIR__) . '/theme/beyourlover-v3/inc/www-shipping-production-candidate-v1.php';

function config_fixture(): array {
    $spec=[
        0=>['free'=>101,'flat'=>203,'min'=>69.0,'cost'=>20.0],
        1=>['free'=>202,'min'=>0.0],
        3=>['free'=>306,'flat'=>307,'min'=>169.0,'cost'=>30.0],
        4=>['free'=>408,'flat'=>409,'min'=>129.0,'cost'=>20.0],
        5=>['free'=>510,'flat'=>511,'min'=>69.0,'cost'=>10.0],
    ];
    $ledger=[];
    foreach ($spec as $zone=>$data) {
        $free=$zone===1
            ? ['requires'=>'no','min_amount'=>'','ignore_discounts'=>'no']
            : ['requires'=>'min_amount','min_amount'=>(string)$data['min'],
               'ignore_discounts'=>'no'];
        $GLOBALS['options']['woocommerce_free_shipping_'.$data['free'].'_settings']=$free;
        $GLOBALS['zoneMethods'][$zone] = [new ShippingMethodStub('free_shipping',$data['free'])];
        $entry=['free_id'=>$data['free'],
            'free_digest'=>byl_www_ship_settings_digest($free),
            'minimum'=>$data['min'],
            'before_discount'=>false];
        if (isset($data['flat'])) {
            $flat=['cost'=>(string)$data['cost'],'class_cost_1939'=>'30',
                'class_cost_1874'=>'0','no_class_cost'=>'',
                'type'=>'class'];
            $GLOBALS['options']['woocommerce_flat_rate_'.$data['flat'].'_settings']=$flat;
            $GLOBALS['zoneMethods'][$zone][] = new ShippingMethodStub('flat_rate',$data['flat']);
            $entry += ['flat_id'=>$data['flat'],
                'flat_digest'=>byl_www_ship_settings_digest($flat),
                'flat_cost'=>$data['cost']];
        }
        $ledger[$zone]=$entry;
    }
    return $ledger;
}
function mk_item(int $parent, float $discounted, int $qty=1): array {
    return ['product_id'=>$parent,'line_total'=>$discounted,'quantity'=>$qty];
}
function mk_package(int $zone, array $items): array {
    return ['destination'=>['zone'=>$zone,'country'=>$zone===1?'HK':'GB'],
        'contents'=>$items,'contents_cost'=>byl_www_ship_subtotal($items)];
}
function mk_rates(int $zone, array $ledger): array {
    $entry=$ledger[$zone];
    $rates=[];
    $free=$entry['free_id'];
    $rates["free_shipping:$free"]=new WC_Shipping_Rate(
        "free_shipping:$free",'Free Shipping',0.0,[],'free_shipping',$free
    );
    if (isset($entry['flat_id'])) {
        $flat=$entry['flat_id'];
        $rates["flat_rate:$flat"]=new WC_Shipping_Rate(
            "flat_rate:$flat",'Flat Rate',(float)$entry['flat_cost'],
            ['cached'=>500],'flat_rate',$flat
        );
    }
    return $rates;
}

check_prod(!byl_www_ship_armed(),'not armed with missing Owner approval');
check_prod(!byl_www_ship_gate(),'default OFF regardless of Woo shipping');
check_prod($GLOBALS['hooks']===[],'production module never self-registers hooks');
check_prod(byl_www_ship_product_kind(mk_item(32251,98))==='special','SAKURA identity');
check_prod(byl_www_ship_product_kind(mk_item(9145,179))==='special','GREEN identity');
check_prod(byl_www_ship_product_kind(mk_item(9153,179))==='special','RED identity');
check_prod(byl_www_ship_product_kind(mk_item(38047,59))==='pillow','PILLOW parent');
check_prod(byl_www_ship_product_kind(mk_item(37763,20))==='ordinary','ordinary identity');
$offPackage=mk_package(0,['normal'=>mk_item(37763,20)]);
check_prod(byl_www_ship_packages([$offPackage])===[$offPackage],'package filter inert while OFF');
$ledger=config_fixture();
$offRates=mk_rates(0,$ledger);
check_prod(byl_www_ship_rates($offRates,$offPackage)===$offRates,'rate filter inert while OFF');
check_prod(byl_www_ship_contract()===null,'missing Owner actual WWW ledger keeps OFF');
define('BYL_WWW_SHIPPING_V1_APPROVED',true);
define('BYL_WWW_SHIPPING_V1_METHOD_CONTRACT',$ledger);
check_prod(!byl_www_ship_gate(),'approval alone cannot switch on');
$GLOBALS['options']['byl_www_shipping_v1_enabled']='yes';
check_prod(byl_www_ship_gate(),'all explicit arm guards + mock Owner ledger match');
check_prod(byl_www_ship_settings_digest(['b'=>2,'a'=>['z'=>4,'x'=>3]])
    ===byl_www_ship_settings_digest(['a'=>['x'=>3,'z'=>4],'b'=>2]),
    'nested Woo option hash order normalized');
byl_www_ship_boot();
check_prod(isset($GLOBALS['hooks']['woocommerce_cart_shipping_packages'])
    && $GLOBALS['hooks']['woocommerce_cart_shipping_packages'][1]===970,
    'manual boot installs first package splitter');
check_prod(isset($GLOBALS['hooks']['woocommerce_package_rates'])
    && $GLOBALS['hooks']['woocommerce_package_rates'][1]===100200,
    'manual boot installs final rate filter');
foreach ([0,1,3,4,5] as $zoneId) {
    $ord=mk_package($zoneId,['normal'=>mk_item(37763,20)]);
    $regularPart=byl_www_ship_packages([$ord]);
    check_prod(count($regularPart)===1 &&
        $regularPart[0]['byl_www_ship_kind']==='ordinary',
        "zone $zoneId ordinary package identity");
    $filtered=byl_www_ship_rates(mk_rates($zoneId,$ledger),$regularPart[0]);
    $freeKey='free_shipping:'.$ledger[$zoneId]['free_id'];
    check_prod(($zoneId===1)===isset($filtered[$freeKey]),
        "zone $zoneId minimum removes only under-threshold ordinary Free");
    $qualified=mk_package($zoneId,['normal'=>mk_item(37763,180)]);
    $qualPart=byl_www_ship_packages([$qualified]);
    $qualRates=byl_www_ship_rates(mk_rates($zoneId,$ledger),$qualPart[0]);
    check_prod(isset($qualRates[$freeKey]),
        "zone $zoneId qualifying ordinary keeps actual Woo Free");
    $special=mk_package($zoneId,['green'=>mk_item(9145,179),
        'red'=>mk_item(9153,179)]);
    $specialPart=byl_www_ship_packages([$special]);
    check_prod(count($specialPart)===1 &&
        $specialPart[0]['byl_www_ship_kind']==='special',
        "zone $zoneId multiple specials share one package");
    $specialRate=byl_www_ship_rates(mk_rates($zoneId,$ledger),$specialPart[0]);
    check_prod(count($specialRate)===1 &&
        !str_starts_with((string)key($specialRate),'free_shipping:'),
        "zone $zoneId approved specials never free");
    check_prod(reset($specialRate)->get_cost()===30.0,
        "zone $zoneId exactly USD30 per special shipment");
    check_prod(reset($specialRate)->get_taxes()===[],
        "zone $zoneId recomputes shipping tax from new cost");
    $pillow=mk_package($zoneId,['pillow'=>mk_item(38047,118,2)]);
    $pillowPart=byl_www_ship_packages([$pillow]);
    check_prod($pillowPart[0]['byl_www_ship_pillow_qty']===2,
        "zone $zoneId pillow unit count not shipment count");
    $pillowRate=byl_www_ship_rates(mk_rates($zoneId,$ledger),$pillowPart[0]);
    check_prod(count($pillowRate)===1 &&
        reset($pillowRate)->get_cost()===60.0,
        "zone $zoneId pillow 2×USD30");
    $mixed=mk_package($zoneId,['normal'=>mk_item(37763,20),
        'special'=>mk_item(32251,98),
        'pillow'=>mk_item(38047,59)]);
    $split=byl_www_ship_packages([$mixed]);
    check_prod(count($split)===3 && $split[0]['byl_www_ship_kind']==='ordinary'
       && $split[1]['byl_www_ship_kind']==='special'
       && $split[2]['byl_www_ship_kind']==='pillow',
       "zone $zoneId mixed packages split exactly once");
    $total=0.0;
    foreach ($split as $pkg) {
        $calculated=byl_www_ship_rates(mk_rates($zoneId,$ledger),$pkg);
        $total+=reset($calculated)->get_cost();
    }
    $normalCost=$zoneId===1?0.0:(float)$ledger[$zoneId]['flat_cost'];
    check_prod(abs($total-($normalCost+30.0+30.0))<0.000001,
        "zone $zoneId ordinary+special+pillow charged separately");
}
$base=mk_rates(5,$ledger);
$normal=byl_www_ship_packages([mk_package(5,['normal'=>mk_item(37763,68.99)])])[0];
check_prod(!isset(byl_www_ship_rates($base,$normal)['free_shipping:510']),
    'post-coupon USD68.99 cannot unlock zone5 USD69');
$at69=byl_www_ship_packages([mk_package(5,['normal'=>mk_item(37763,69.0)])])[0];
check_prod(isset(byl_www_ship_rates($base,$at69)['free_shipping:510']),
    'post-coupon exact USD69 retains original Woo free');
$GLOBALS['coupons']=['BYL49'];
check_prod(isset(byl_www_ship_rates($base,$normal)['free_shipping:510']),
    'BYL49 special existing-code protection preserved');
$GLOBALS['coupons']=[];
$unknown=mk_package(999,['red'=>mk_item(9153,179)]);
$unknown[ 'byl_www_ship_kind' ]='special';
check_prod(byl_www_ship_rates($base,$unknown)===[],
    'unsupported zone does not gain invented shipping');
$lost=mk_package(5,['regular'=>mk_item(37763,20),'red'=>mk_item(9153,179)]);
check_prod(byl_www_ship_rates($base,$lost)===[],
    'lost package splitter fails closed instead of free');
$specialPkg=byl_www_ship_packages([mk_package(5,['red'=>mk_item(9153,179)])])[0];
$wrongRate=['free_shipping:510'=>$base['free_shipping:510']];
check_prod(byl_www_ship_rates($wrongRate,$specialPkg)===[],
    'missing genuine flat instance cannot silently waive exception fee');
$GLOBALS['currency']='EUR';
check_prod(byl_www_ship_gate() && !byl_www_ship_currency_ok(),
    'live ledger verified but unreviewed FX unsupported');
check_prod(byl_www_ship_rates($base,$specialPkg)===[],
    'EUR is blocked while Owner-approved runtime armed');
check_prod(byl_www_ship_rates($base,$normal)===[],
    'EUR ordinary is also blocked not silently shipped');
$GLOBALS['currency']='USD';
$flatKey='woocommerce_flat_rate_511_settings';
$GLOBALS['options'][$flatKey]['class_cost_1939']='0'; // drift including class cost.
check_prod(!byl_www_ship_gate(),'class-rate drift invalidates entire release ledger');
check_prod(byl_www_ship_rates($base,$specialPkg)===[],
    'drift never permits free special freight');
check_prod(byl_www_ship_rates($base,$normal)===[],
    'drift never permits base Woo free method');
$GLOBALS['options'][$flatKey]['class_cost_1939']='30';
check_prod(byl_www_ship_gate(),'known settings restored by local stub');
$GLOBALS['zoneMethods'][5][] = new ShippingMethodStub('free_shipping',999);
check_prod(!byl_www_ship_gate(),'unexpected enabled Woo Free instance invalidates ledger');
check_prod(byl_www_ship_rates($base,$specialPkg)===[],
    'unexpected enabled free method cannot bypass special charge');
array_pop($GLOBALS['zoneMethods'][5]);
$GLOBALS['site']='dev.beyourlover.com';
check_prod(!byl_www_ship_armed(),'Dev never armed by WWW approval');
check_prod(byl_www_ship_rates($base,$specialPkg)===$base,
    'WWW candidate inert on Dev');
$GLOBALS['site']='www.beyourlover.com';
$GLOBALS['environment']='staging';
check_prod(!byl_www_ship_armed(),'WWW must be production environment');
$GLOBALS['environment']='production';
$source=file_get_contents(dirname(__DIR__)
    .'/theme/beyourlover-v3/inc/www-shipping-production-candidate-v1.php');
foreach (['update_option(', 'delete_option(', 'wp_insert_post(',
    'wp_mail(', 'woocommerce_checkout_create_order'] as $needle) {
    check_prod(!str_contains($source,$needle),
        "source cannot mutate WP options/orders/email or self-bootstrap: $needle");
}
check_prod(!preg_match('/^\s*add_action\s*\(/m', $source),
    'module has no runtime bootstrap registrations');
echo "PASS WWW shipping production candidate OFF: {$GLOBALS['assertions']} offline assertions; no site access/no orders/no settings writes\n";
