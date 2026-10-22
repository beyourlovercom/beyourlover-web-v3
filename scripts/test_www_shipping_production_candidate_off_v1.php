<?php
/** No-network, no-WordPress, no-order test of an UNWIRED production candidate. */
declare(strict_types=1);
define('ABSPATH', __DIR__);
// Isolated fake WordPress plugin header fixture in the local system TMP.
// Never install a plugin or access any real WordPress filesystem.
$mockPluginRoot = sys_get_temp_dir() . '/byl_www_fx_test_' . getmypid();
$mockPluginDir = $mockPluginRoot . '/yaycurrency-pro';
if (!is_dir($mockPluginDir) && !mkdir($mockPluginDir, 0700, true)) {
    throw new RuntimeException('Cannot create offline FX plugin fixture');
}
file_put_contents($mockPluginDir.'/yay-currency.php', "<?php\n/* Mock version header only. */\n");
define('WP_PLUGIN_DIR', $mockPluginRoot);
register_shutdown_function(static function () use ($mockPluginRoot,$mockPluginDir): void {
    @unlink($mockPluginDir.'/yay-currency.php');
    @rmdir($mockPluginDir);
    @rmdir($mockPluginRoot);
});
$GLOBALS['mockYayVersion'] = '3.2.1';
$GLOBALS['site'] = 'www.beyourlover.com';
$GLOBALS['environment'] = 'production';
$GLOBALS['currency'] = 'USD';
$GLOBALS['effective_currency'] = 'USD';
$GLOBALS['zoneLocationVersion'] = 'v1';
$GLOBALS['classMembers'] = ['38047','32251','9153','9145'];
$GLOBALS['options'] = [
    'byl_www_shipping_v1_enabled' => 'no',
    'active_plugins' => ['yaycurrency-pro/yay-currency.php']
];
$GLOBALS['hooks'] = [];
$GLOBALS['assertions'] = 0;
$GLOBALS['coupons'] = [];
$GLOBALS['nativeFreeCouponObjects'] = [];

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
function get_file_data(string $pluginFile, array $headers, string $context): array {
    if ($pluginFile !== WP_PLUGIN_DIR.'/yaycurrency-pro/yay-currency.php'
        || $context !== 'plugin'
        || !isset($headers['Version'])) {
        return [];
    }
    return ['Version' => $GLOBALS['mockYayVersion']];
}
function get_objects_in_term(int $term, string $taxonomy) {
    return ($term === 1939 && $taxonomy === 'product_shipping_class')
        ? $GLOBALS['classMembers'] : [];
}
function get_woocommerce_currency(): string { return $GLOBALS['currency']; }
function byl_www_shipping_v1_verified_checkout_currency(): string {
    return $GLOBALS['effective_currency'];
}
function byl_www_shipping_v1_verified_legacy_vendor_currency(): ?string {
    return $GLOBALS['vendor_currency'] ?? null;
}
function add_filter(string $name, $callable, int $priority, int $args): void {
    $GLOBALS['hooks'][$name] = [$callable, $priority, $args];
}
// Synthetic vendor filters ONLY; these do not contain any commercial
// plugin code, live exchange rates or site/customer data.
function has_filter(string $name) {
    return $GLOBALS['mockFxHooks'][$name] ?? false;
}
function apply_filters(string $name, $value) {
    if (($GLOBALS['mockFxThrows'] ?? false) === true) {
        throw new RuntimeException('synthetic conversion failed');
    }
    if (($GLOBALS['mockFxInvalid'] ?? false) === true) {
        return 'not-a-number';
    }
    $factor = (float)($GLOBALS['mockFxRate'] ?? 1.0);
    if ($name === 'yay_currency_convert_price') {
        return round((float)$value * $factor, 4);
    }
    if ($name === 'yay_currency_revert_price') {
        return $factor > 0.0
            ? ((float)$value / $factor) * (float)($GLOBALS['mockRevertDrift'] ?? 1.0)
            : null;
    }
    return $value;
}
class ShippingZoneStub {
    public function __construct(private int $id) {}
    public function get_id(): int { return $this->id; }
    public function get_shipping_methods(bool $enabledOnly = true): array {
        return $GLOBALS['zoneMethods'][$this->id] ?? [];
    }
    public function get_zone_locations(): array {
        return [['type'=>'country', 'code'=>(string)$this->id,
            'version'=>$GLOBALS['zoneLocationVersion']]];
    }
    public function get_zone_order(): int { return $this->id; }
}
class WC_Shipping_Zones {
    // Woo get_zones() excludes fallback Zone 0.
    public static function get_zones(): array {
        $zones = $GLOBALS['zoneMethods'] ?? [];
        unset($zones[0]);
        return $zones;
    }
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
    public function get_tax_status(): string { return $this->taxStatus; }
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
class NativeWooCouponStub {
    public function __construct(
        private bool $valid, private bool $freeShipping
    ) {}
    public function is_valid(): bool { return $this->valid; }
    public function get_free_shipping(): bool { return $this->freeShipping; }
}
class ProdShippingCartStub {
    public function get_applied_coupons(): array { return $GLOBALS['coupons']; }
    public function get_coupons(): array { return $GLOBALS['nativeFreeCouponObjects']; }
}
function WC(): object { return (object)['cart'=>new ProdShippingCartStub()]; }

require __DIR__ . '/release-candidates/www-shipping-production-candidate-v1.php';

function config_fixture(): array {
    $spec=[
        // Exact IDs were read from the authenticated WWW Woo admin.
        // Actual WWW Zone0 "either" rule is preserved for valid free coupons.
        0=>['free'=>1,'flat'=>3,'min'=>69.0,'cost'=>20.0],
        1=>['free'=>2,'min'=>0.0],
        3=>['free'=>6,'flat'=>5,'min'=>169.0,'cost'=>30.0],
        4=>['free'=>8,'flat'=>7,'min'=>129.0,'cost'=>20.0],
        5=>['free'=>9,'flat'=>10,'min'=>69.0,'cost'=>10.0],
    ];
    $ledger=[];
    foreach ($spec as $zone=>$data) {
        $required = $zone===1 ? 'no' : ($zone===0 ? 'either' : 'min_amount');
        $free=['requires'=>$required,
            'min_amount'=>$zone===1 ? '' : (string)$data['min'],
            'ignore_discounts'=>'no'];
        $GLOBALS['options']['woocommerce_free_shipping_'.$data['free'].'_settings']=$free;
        $GLOBALS['zoneMethods'][$zone] = [new ShippingMethodStub('free_shipping',$data['free'])];
        $entry=['free_id'=>$data['free'],
            'free_digest'=>byl_www_ship_settings_digest($free),
            'minimum'=>$data['min'],
            'before_discount'=>false,
            'free_requirement'=>$required];
        if (isset($data['flat'])) {
            // Exact known WWW admin class fees are deliberately *not*
            // final special charge: Zone0 has base20+class30.
            $class1939=[0=>'30',3=>'20',4=>'10',5=>'20'][$zone];
            $flat=['cost'=>(string)$data['cost'],
                'class_cost_932'=>in_array($zone,[0,3],true)?'0':'',
                'class_cost_1874'=>in_array($zone,[0,3],true)?'10':'0',
                'class_cost_1939'=>$class1939,
                'no_class_cost'=>$zone===0?'0':'',
                'type'=>$zone===3?'order':'class',
                'tax_status'=>'taxable'];
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
check_prod(!byl_www_ship_currency_ok(),
    'Woo base USD cannot authorize YayCurrency without reviewed FX contract');
check_prod(!byl_www_ship_yaycurrency_prerequisite(),
    'actual WWW YayCurrency Pro 3.2.1 is below fixed-shipping version floor');
define('BYL_WWW_SHIPPING_V1_FX_REVIEWED',true);
check_prod(!byl_www_ship_currency_ok(),
    'approval constant alone must not enable legacy YayCurrency 3.2.1');
$GLOBALS['mockYayVersion']='3.3.3';
check_prod(!byl_www_ship_yaycurrency_prerequisite(),
    'YayCurrency 3.3.3 still precedes Order-type shipping fix 3.3.4');
$GLOBALS['mockYayVersion']='3.3.4';
check_prod(!byl_www_ship_yaycurrency_prerequisite(),
    '3.3.4 has Order shipping fix but is below source-only security review floor');
check_prod(!byl_www_ship_currency_ok(),
    'approved FX constant cannot override 3.3.5 security version floor');
$GLOBALS['mockYayVersion']='3.3.5';
check_prod(byl_www_ship_yaycurrency_prerequisite(),
    '3.3.5 is minimum source security prerequisite, never live FX UAT');
check_prod(byl_www_ship_currency_ok(),
    'reviewed bridge and USD at source prerequisite can proceed in offline mock');
$GLOBALS['options']['active_plugins']=[];
check_prod(!byl_www_ship_currency_ok(),
    'inactive YayCurrency plugin fails version/FX prerequisite');
$GLOBALS['options']['active_plugins']=['yaycurrency-pro/yay-currency.php'];
define('BYL_WWW_SHIPPING_V1_APPROVED',true);
define('BYL_WWW_SHIPPING_V1_METHOD_CONTRACT',$ledger);
check_prod(!byl_www_ship_gate(),'approval alone cannot switch on');
$GLOBALS['options']['byl_www_shipping_v1_enabled']='yes';
check_prod(byl_www_ship_gate(),'all explicit arm guards + mock Owner ledger match');
check_prod(byl_www_ship_product_classes_match(),
    'actual class1939 four-parents matching WWW list');
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
    check_prod(isset($regularPart[0]['byl_www_ship_runtime_marker'])
        && preg_match('/^[a-f0-9]{64}$/D',
            $regularPart[0]['byl_www_ship_runtime_marker'])===1,
        "zone $zoneId Woo cache invalidation marker present while armed");
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
check_prod(!isset(byl_www_ship_rates($base,$normal)['free_shipping:9']),
    'post-coupon USD68.99 cannot unlock zone5 USD69');
$at69=byl_www_ship_packages([mk_package(5,['normal'=>mk_item(37763,69.0)])])[0];
check_prod(isset(byl_www_ship_rates($base,$at69)['free_shipping:9']),
    'post-coupon exact USD69 retains original Woo free');
$GLOBALS['coupons']=['BYL49'];
check_prod(!isset(byl_www_ship_rates($base,$normal)['free_shipping:9']),
    'literal BYL49 text alone never overrides Woo native free shipping');
check_prod(!byl_www_ship_valid_native_free_coupon(),
    'plain BYL49 code without valid Woo coupon grants nothing');
$GLOBALS['nativeFreeCouponObjects']=['BYL49'=>new NativeWooCouponStub(true,false)];
check_prod(!byl_www_ship_valid_native_free_coupon(),
    'valid normal discount coupon without free_shipping entitlement grants nothing');
$GLOBALS['nativeFreeCouponObjects']=['GIFT'=>new NativeWooCouponStub(false,true)];
check_prod(!byl_www_ship_valid_native_free_coupon(),
    'invalid coupon marked free_shipping grants nothing');
$GLOBALS['nativeFreeCouponObjects']=['NATIVEFREE'=>new NativeWooCouponStub(true,true)];
check_prod(byl_www_ship_valid_native_free_coupon(),
    'verified Woo native free shipping coupon recognized independently of text code');
check_prod(!isset(byl_www_ship_rates($base,$normal)['free_shipping:9']),
    'Zone5 minimum-only rule ignores coupon bypass even if coupon valid');
$GLOBALS['nativeFreeCouponObjects']=[];
$GLOBALS['coupons']=[];
$unknown=mk_package(999,['red'=>mk_item(9153,179)]);
$unknown[ 'byl_www_ship_kind' ]='special';
check_prod(byl_www_ship_rates($base,$unknown)===[],
    'unsupported zone does not gain invented shipping');
$lost=mk_package(5,['regular'=>mk_item(37763,20),'red'=>mk_item(9153,179)]);
check_prod(byl_www_ship_rates($base,$lost)===[],
    'lost package splitter fails closed instead of free');
$specialPkg=byl_www_ship_packages([mk_package(5,['red'=>mk_item(9153,179)])])[0];
$taxless=mk_rates(5,$ledger);
$taxless['flat_rate:10']=new WC_Shipping_Rate(
    'flat_rate:10', 'Flat rate', 10.0, [], 'flat_rate', 10, 'none'
);
check_prod(byl_www_ship_rates($taxless,$specialPkg)===[],
    'non-taxable Woo Flat quote cannot be silently turned taxable');
$GLOBALS['taxReturn']=['TAX-ID-1'=>1.25];
$taxed=byl_www_ship_rates(mk_rates(5,$ledger),$specialPkg);
check_prod(reset($taxed)->get_taxes()===['TAX-ID-1'=>1.25],
    'special rate propagates positive Woo shipping tax result');
unset($GLOBALS['taxReturn']);
$wrongRate=['free_shipping:9'=>$base['free_shipping:9']];
check_prod(byl_www_ship_rates($wrongRate,$specialPkg)===[],
    'missing genuine flat instance cannot silently waive exception fee');
// YayCurrency public 3.3.5 source registers its currency conversion
// on Woo package rates at priority 10; WWW candidate's actual PHP
// rate callback is priority 100200 (checked above). The later callback
// MUST NOT replace an already-converted local currency amount with a
// literal 30, so fail-closed is required until Pro FX order is verified.
// These FX factors are synthetic regression fixtures, NOT live quotes.
$syntheticRates=['EUR'=>0.93,'GBP'=>0.79,'AUD'=>1.54,'CAD'=>1.36];
check_prod($GLOBALS['hooks']['woocommerce_package_rates'][1] > 10,
    'FX-aware shipping release callback follows published YayCurrency priority10');
foreach ($syntheticRates as $chosen => $illustrativeRate) {
    $GLOBALS['effective_currency']=$chosen;
    $hypotheticalConverted=mk_rates(5,$ledger);
    $hypotheticalConverted['flat_rate:10']->set_cost(
        round(10.0*$illustrativeRate,2)
    );
    check_prod($hypotheticalConverted['flat_rate:10']->get_cost()
        !== $base['flat_rate:10']->get_cost(),
        "$chosen vendor-first fixture has an already-converted local rate");
    check_prod(byl_www_ship_rates($hypotheticalConverted,$specialPkg)===[],
        "$chosen cannot get a late literal USD30 override after FX conversion");
    check_prod(byl_www_ship_rates($hypotheticalConverted,$normal)===[],
        "$chosen normal rates also require FX-reviewed bridge");
}
$GLOBALS['effective_currency']='USD';
foreach (['GBP', 'EUR', 'AUD', 'CAD'] as $selectedCurrency) {
    $GLOBALS['effective_currency']=$selectedCurrency;
    check_prod(get_woocommerce_currency()==='USD'
        && !byl_www_ship_currency_ok(),
        "$selectedCurrency checkout must not infer selected currency from USD base");
    check_prod(byl_www_ship_rates($base,$specialPkg)===[],
        "$selectedCurrency special shipment blocks unsafe USD30 rate");
    check_prod(byl_www_ship_rates($base,$normal)===[],
        "$selectedCurrency ordinary rate also blocked without FX converter");
}
$GLOBALS['effective_currency']='USD';
$GLOBALS['currency']='EUR';
check_prod(byl_www_ship_gate() && !byl_www_ship_currency_ok(),
    'live ledger verified but unreviewed FX unsupported');
check_prod(byl_www_ship_rates($base,$specialPkg)===[],
    'EUR is blocked while Owner-approved runtime armed');
check_prod(byl_www_ship_rates($base,$normal)===[],
    'EUR ordinary is also blocked not silently shipped');
$GLOBALS['currency']='USD';
$flatKey='woocommerce_flat_rate_10_settings';
$oldShipMarker=$specialPkg['byl_www_ship_runtime_marker'];
$GLOBALS['options'][$flatKey]['class_cost_1939']='0'; // drift including class cost.
$changedShipMarker=byl_www_ship_packages([
    mk_package(5,['red'=>mk_item(9153,179)])
])[0]['byl_www_ship_runtime_marker'];
check_prod($oldShipMarker!==$changedShipMarker,
    'Woo session package hash invalidated on Flat shipping class cost drift');
check_prod(!byl_www_ship_gate(),'class-rate drift invalidates entire release ledger');
check_prod(byl_www_ship_rates($base,$specialPkg)===[],
    'drift never permits free special freight');
check_prod(byl_www_ship_rates($base,$normal)===[],
    'drift never permits base Woo free method');
$GLOBALS['options'][$flatKey]['class_cost_1939']='20';
check_prod(byl_www_ship_gate(),'known settings restored by local stub');
// Actual WWW Zone0 Free #1 is "either": honor only a genuine Woo
// valid free-shipping coupon, and only on a regular-item package.
$zone0Free='woocommerce_free_shipping_1_settings';
$zone0Low=byl_www_ship_packages([mk_package(0,['normal'=>mk_item(37763,68.99)])])[0];
$zone0LowRates=mk_rates(0,$ledger);
check_prod(byl_www_ship_gate(),
    'actual native Woo Zone0 either setting matches the reviewed ledger');
check_prod(!isset(byl_www_ship_rates($zone0LowRates,$zone0Low)['free_shipping:1']),
    'Zone0 regular USD68.99 with no true free coupon still owes Flat USD20');
$GLOBALS['coupons']=['BYL49'];
check_prod(!isset(byl_www_ship_rates($zone0LowRates,$zone0Low)['free_shipping:1']),
    'plain BYL49 text alone cannot waive Zone0 ordinary shipping');
$GLOBALS['nativeFreeCouponObjects']=['SAMPLE'=>new NativeWooCouponStub(true,false)];
check_prod(!isset(byl_www_ship_rates($zone0LowRates,$zone0Low)['free_shipping:1']),
    'valid discount-only Woo coupon does not qualify for free shipping');
$GLOBALS['nativeFreeCouponObjects']=['SAMPLE'=>new NativeWooCouponStub(false,true)];
check_prod(!isset(byl_www_ship_rates($zone0LowRates,$zone0Low)['free_shipping:1']),
    'invalid free-marked Woo coupon cannot qualify for free shipping');
$GLOBALS['nativeFreeCouponObjects']=['VALID-FREE'=>new NativeWooCouponStub(true,true)];
check_prod(isset(byl_www_ship_rates($zone0LowRates,$zone0Low)['free_shipping:1']),
    'native valid free-shipping coupon preserves ordinary Zone0 free method');
$zone0Mix=byl_www_ship_packages([mk_package(0,[
    'regular'=>mk_item(37763,20),
    'special'=>mk_item(9153,179),
    'pillow'=>mk_item(38047,118,2)
])]);
$zone0FreeTotal=0.0;
foreach ($zone0Mix as $part) {
    $one=byl_www_ship_rates(mk_rates(0,$ledger),$part);
    if ($part['byl_www_ship_kind']==='ordinary') {
        // Native Woo legitimately offers Free and Flat concurrently; only
        // the customer's selected Free method is zero. Both may be shown.
        check_prod(isset($one['free_shipping:1'])
            && isset($one['flat_rate:3']),
            'valid Woo coupon enables free and preserves normal paid option');
        $zone0FreeTotal+=$one['free_shipping:1']->get_cost();
    } else {
        check_prod(count($one)===1 && key($one)==='flat_rate:3',
            'special and PILLOW packages retain only mandatory paid rate');
        $zone0FreeTotal+=reset($one)->get_cost();
    }
}
check_prod($zone0FreeTotal===90.0,
    'valid Woo free coupon waives regular freight only; specials30 and 2 pillows60 remain paid');
$GLOBALS['nativeFreeCouponObjects']=[];
$GLOBALS['coupons']=[];
$GLOBALS['options'][$zone0Free]['requires']='coupon';
check_prod(!byl_www_ship_gate(),
    'Zone0 coupon-only requirement cannot pass approved either contract');
$GLOBALS['options'][$zone0Free]['requires']='both';
check_prod(!byl_www_ship_gate(),
    'Zone0 coupon AND amount requirement cannot pass approved either contract');
$GLOBALS['options'][$zone0Free]['requires']='min_amount';
check_prod(!byl_www_ship_gate(),
    'changing native Zone0 either to min_amount without new ledger is forbidden');
check_prod(byl_www_ship_rates(mk_rates(0,$ledger),$zone0Low)===[],
    'unexpected Zone0 requirement drift is fail-closed');
$GLOBALS['options'][$zone0Free]['requires']='either';
check_prod(byl_www_ship_gate(),'reviewed native WWW Zone0 either requirement restored');
$beforeZoneMove = byl_www_ship_packages([
    mk_package(5,['red'=>mk_item(9153,179)])
])[0]['byl_www_ship_runtime_marker'];
$GLOBALS['zoneLocationVersion']='v2';
$afterZoneMove = byl_www_ship_packages([
    mk_package(5,['red'=>mk_item(9153,179)])
])[0]['byl_www_ship_runtime_marker'];
check_prod($beforeZoneMove!==$afterZoneMove,
    'Woo package hash changes when destination zone coverage drifts');
$GLOBALS['zoneLocationVersion']='v1';
$GLOBALS['zoneMethods'][6]=[
    new ShippingMethodStub('free_shipping',600)
];
check_prod(!byl_www_ship_gate(),
    'new higher-priority Woo shipping zone invalidates approved zone set');
unset($GLOBALS['zoneMethods'][6]);
check_prod(byl_www_ship_gate(),'reviewed zone set restored');
$oldClassMarker=byl_www_ship_runtime_fingerprint();
$GLOBALS['classMembers'][]='50000';
check_prod(!byl_www_ship_gate(),
    'fifth product assigned to class1939 fails WWW release gate');
check_prod(byl_www_ship_runtime_fingerprint()!==$oldClassMarker,
    'shipping class membership drift invalidates cached Woo quote');
check_prod(byl_www_ship_rates($base,$specialPkg)===[],
    'new class member cannot bypass exception fee via stale Free rate');
array_pop($GLOBALS['classMembers']);
check_prod(byl_www_ship_gate(),'verified four-product class membership restored');
$GLOBALS['options'][$flatKey]['tax_status']='none';
check_prod(!byl_www_ship_gate(),
    'native Woo non-taxable Flat setting drift invalidates release ledger');
$GLOBALS['options'][$flatKey]['tax_status']='taxable';
check_prod(byl_www_ship_gate(),'taxable setting restored');
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

// The next block is OFF-by-default compatibility PROPOSAL for legacy Pro
// 3.2.1. It does not prove actual vendor hook order or currency behavior.
$GLOBALS['mockYayVersion']='3.2.1';
$GLOBALS['options']['woocommerce_currency']='USD';
$GLOBALS['mockFxRate']=0.80;
$GLOBALS['effective_currency']='GBP';
$GLOBALS['vendor_currency']='GBP';
check_prod(!byl_www_ship_legacy321_supported(),
    'legacy cannot run without separate Owner-reviewed compatibility switch');
define('BYL_WWW_SHIPPING_V1_LEGACY_321_REVIEWED', true);
check_prod(!byl_www_ship_currency_ok(),
    'legacy cannot run without both vendor conversion and revert hooks');
$GLOBALS['mockFxHooks']=[
    'yay_currency_convert_price'=>10,
    'yay_currency_revert_price'=>10,
];
check_prod(byl_www_ship_legacy321_supported(),
    'explicit legacy switch and synthetic vendor hooks/currency can pass preflight');
$GLOBALS['vendor_currency']='EUR';
check_prod(!byl_www_ship_currency_ok(),
    'GBP display with EUR vendor engine is a hard stop even with roundtrip');
$GLOBALS['vendor_currency']='GBP';
check_prod(byl_www_ship_currency_ok(),
    'legacy GBP mock requires verified round-trip before any shipping rate');
check_prod(abs((float)byl_www_ship_legacy321_fee(30.0)-24.0)<0.001,
    'GBP mock $30 shipping converts exactly once to 24');
$legacyPkg=byl_www_ship_packages([
    mk_package(5,['red'=>mk_item(9153,179),'pillow'=>mk_item(38047,118,2),
        'normal'=>mk_item(37763,20)])
]);
check_prod(count($legacyPkg)===3,
    'legacy mixed cart isolates ordinary special and pillow');
$legacyRates=mk_rates(5,$ledger);
$legacyRates['flat_rate:10']->set_cost(8.0);
foreach ($legacyPkg as $part) {
    $charged=byl_www_ship_rates($legacyRates,$part);
    $kind=$part['byl_www_ship_kind'];
    if ($kind==='ordinary') {
        check_prod(!isset($charged['free_shipping:9'])
            && isset($charged['flat_rate:10'])
            && abs($charged['flat_rate:10']->get_cost()-8.0)<0.001,
            'legacy ordinary sub-threshold uses verified converted native flat');
    } else {
        $rate=reset($charged);
        check_prod(is_object($rate)
            && abs($rate->get_cost()-($kind==='special'?24.0:48.0))<0.001,
            "legacy GBP $30 special parcel versus two-unit $60 pillow");
    }
}
$ordinaryPart=$legacyPkg[0];
check_prod($ordinaryPart['byl_www_ship_kind']==='ordinary',
    'package ordering preserved ordinary first');
$unconverted=mk_rates(5,$ledger);
check_prod(byl_www_ship_rates($unconverted,$ordinaryPart)===[],
    'unconverted GBP native flat rejected instead of undercharging');
$zone3Legacy=byl_www_ship_packages([
    mk_package(3,['ordinary'=>mk_item(37763,80)])
])[0];
$zone3Rates=mk_rates(3,$ledger);
check_prod(byl_www_ship_rates($zone3Rates,$zone3Legacy)===[],
    'legacy Zone3 Order-type buggy native unconverted rate fails closed');
$zone3Rates['flat_rate:5']->set_cost(24.0);
$zone3Accepted=byl_www_ship_rates($zone3Rates,$zone3Legacy);
check_prod(isset($zone3Accepted['flat_rate:5']),
    'legacy Zone3 mock allows only matching native converted Order-type');
$beforeFx=byl_www_ship_runtime_fingerprint();
$GLOBALS['mockFxRate']=0.79;
check_prod(byl_www_ship_runtime_fingerprint()!==$beforeFx,
    'legacy vendor-rate changes invalidate Woo package cache fingerprint');
foreach (['USD'=>1.0,'EUR'=>0.92,'GBP'=>0.79,'AUD'=>1.51,'CAD'=>1.35] as $code=>$factor) {
    $GLOBALS['effective_currency']=$code;
    $GLOBALS['vendor_currency']=$code;
    $GLOBALS['mockFxRate']=$factor;
    check_prod(byl_www_ship_currency_ok(),
        "$code legacy fixture remains fail-closed unless converter roundtrip valid");
    check_prod(abs((float)byl_www_ship_legacy321_fee(30)-round(30*$factor,2))<0.001,
        "$code base USD 30 fixture exactly once");
}
$GLOBALS['mockRevertDrift']=1.10;
check_prod(!byl_www_ship_currency_ok(),
    'corrupt FX reverse mismatch blocks release');
unset($GLOBALS['mockRevertDrift']);
$GLOBALS['mockFxInvalid']=true;
check_prod(!byl_www_ship_currency_ok(),
    'nonnumeric vendor output blocks release');
unset($GLOBALS['mockFxInvalid']);
$GLOBALS['mockFxThrows']=true;
check_prod(!byl_www_ship_currency_ok(),
    'vendor exception blocks release without changing checkout total');
unset($GLOBALS['mockFxThrows']);
$GLOBALS['effective_currency']='JPY';
check_prod(!byl_www_ship_currency_ok(),
    'unknown selected currency never receives fabricated shipping');
$GLOBALS['effective_currency']='USD';
$GLOBALS['vendor_currency']='USD';
$GLOBALS['mockFxRate']=1.0;
$GLOBALS['mockYayVersion']='3.3.3';
check_prod(!byl_www_ship_currency_ok(),
    'legacy reviewed flag must not authorize untested Pro 3.3.3');
$GLOBALS['mockYayVersion']='3.2.1';
$GLOBALS['options']['woocommerce_currency']='EUR';
check_prod(!byl_www_ship_currency_ok(),
    'legacy cannot silently reinterpret non-USD Woo base');
$GLOBALS['options']['woocommerce_currency']='USD';
check_prod(byl_www_ship_currency_ok(),
    'restoring exact 3.2.1 + USD base and trusted hook fixture passes mock');
$GLOBALS['site']='dev.beyourlover.com';
check_prod(!byl_www_ship_armed()
    && byl_www_ship_rates($legacyRates,$legacyPkg[1])===$legacyRates,
    'legacy candidate still inert on Dev even with synthetic valid FX');
$GLOBALS['site']='www.beyourlover.com';
$source=file_get_contents(dirname(__DIR__)
    .'/scripts/release-candidates/www-shipping-production-candidate-v1.php');
foreach (['update_option(', 'delete_option(', 'wp_insert_post(',
    'wp_mail(', 'woocommerce_checkout_create_order'] as $needle) {
    check_prod(!str_contains($source,$needle),
        "source cannot mutate WP options/orders/email or self-bootstrap: $needle");
}
check_prod(!preg_match('/^\s*add_action\s*\(/m', $source),
    'module has no runtime bootstrap registrations');
echo "PASS WWW shipping production candidate OFF: {$GLOBALS['assertions']} offline assertions; no site access/no orders/no settings writes\n";
