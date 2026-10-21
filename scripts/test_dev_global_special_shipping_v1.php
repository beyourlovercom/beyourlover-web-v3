<?php
/**
 * Offline, no WordPress/WWW writes, no plugin installation.
 * Reuses all existing PR153 PHP shipping mocks and their base 63 assertions.
 */
declare(strict_types=1);
require __DIR__ . '/test_dev_restworld_shipping_exceptions_v1.php';
$GLOBALS['host'] = 'dev.beyourlover.com';
$GLOBALS['environment'] = 'staging';
$GLOBALS['enabled'] = 'yes';
$GLOBALS['globalSpecialEnabled'] = 'yes';
require dirname(__DIR__).'/theme/beyourlover-v3/inc/dev-global-special-shipping-v1.php';
check(byl_dev_global_special_active(),'global exception explicit Dev flag ON');
byl_dev_global_special_boot();
check($GLOBALS['hooks']['woocommerce_cart_shipping_packages'][1][1]===960,
    'split after PILLOW900 and Restworld950');
check($GLOBALS['hooks']['woocommerce_package_rates'][1][1]===100020,
    'rates after Restworld100010');
function allzone(int $zone): array {
    return match ($zone) {
        1=>['free_shipping:2'=>new WC_Shipping_Rate('free_shipping:2','free_shipping',0.0)],
        3=>[
            'flat_rate:5'=>new WC_Shipping_Rate('flat_rate:5','flat_rate',50.0),
            'free_shipping:6'=>new WC_Shipping_Rate('free_shipping:6','free_shipping',0.0),
        ],
        4=>[
            'flat_rate:7'=>new WC_Shipping_Rate('flat_rate:7','flat_rate',30.0),
            'free_shipping:8'=>new WC_Shipping_Rate('free_shipping:8','free_shipping',0.0),
        ],
        5=>[
            'flat_rate:10'=>new WC_Shipping_Rate('flat_rate:10','flat_rate',30.0),
            'free_shipping:9'=>new WC_Shipping_Rate('free_shipping:9','free_shipping',0.0),
        ],
    };
}
function allpackage(array $items, int $zone): array {
    $countries=[1=>'HK',3=>'MK',4=>'US',5=>'US'];
    $states=[1=>'HONG KONG',3=>'',4=>'AK',5=>'CA'];
    return [
        'destination'=>['country'=>$countries[$zone],'state'=>$states[$zone]],
        'contents'=>$items,
        'contents_cost'=>byl_dev_restworld_sum($items),
    ];
}
foreach ([1,3,4,5] as $zone) {
    $start=allpackage(['special'=>item(9153,179)],$zone);
    $parts=byl_dev_global_special_packages([$start]);
    check(count($parts)===1,'one special shipment in zone '.$zone);
    check($parts[0]['byl_dev_global_special_kind']==='special30',
        'special package identity in zone '.$zone);
    $rate=byl_dev_global_special_rates(allzone($zone),$parts[0]);
    check(count($rate)===1,'exactly one paid rate zone '.$zone);
    $key=key($rate);
    check(!str_starts_with($key,'free_shipping:'),
        'special is never free zone '.$zone);
    check(current($rate)->get_cost()===30.0,
        'special true USD $30 zone '.$zone);
    $missing=byl_dev_global_special_rates([], $parts[0]);
    check($missing===[],'cannot invent unsupported destination zone '.$zone);
    $mixed=allpackage([
        'regular'=>item(37763,80), 'special'=>item(32251,98)
    ],$zone);
    $split=byl_dev_global_special_packages([$mixed]);
    check(count($split)===2,'regular/special split zone '.$zone);
    check($split[0]['byl_dev_global_special_kind']==='ordinary',
        'regular separated zone '.$zone);
    check($split[1]['byl_dev_global_special_kind']==='special30',
        'special separated zone '.$zone);
    $ordinaryRates=allzone($zone);
    check(byl_dev_global_special_rates($ordinaryRates,$split[0])===$ordinaryRates,
        'ordinary rates preserved exactly zone '.$zone);
    $specialRates=byl_dev_global_special_rates(allzone($zone),$split[1]);
    check(current($specialRates)->get_cost()===30.0,'mixed special paid zone '.$zone);
    $lostMarker=$start;
    check(byl_dev_global_special_rates(allzone($zone),$lostMarker)===[],
        'lost marker must not free special zone '.$zone);
    $pillow=allpackage(['pillow'=>item(38047,59)],$zone);
    $pillow['byl_dev_pillow_kind']='pillow';
    check(byl_dev_global_special_packages([$pillow])[0]===$pillow,
        'PILLOW package stays owned by existing adapter zone '.$zone);
    $pillowRates=allzone($zone);
    check(byl_dev_global_special_rates($pillowRates,$pillow)===$pillowRates,
        'PILLOW rate untouched zone '.$zone);
    $malformed=allpackage(['regular'=>item(37763,20),
        'special'=>item(32251,98),'pillow'=>item(38047,59)],$zone);
    $blocked=byl_dev_global_special_packages([$malformed]);
    check(count($blocked)===1 &&
        $blocked[0]['byl_dev_global_special_kind']==='unsafe_unsplit_pillow',
        'missing PILLOW splitter detected zone '.$zone);
    check(byl_dev_global_special_rates(allzone($zone),$blocked[0])===[],
        'missing PILLOW split cannot bypass fee zone '.$zone);
}
$ordinary=allpackage(['regular'=>item(37763,20)],1);
check(byl_dev_global_special_packages([$ordinary])[0]===$ordinary,
    'Hong Kong normal package unchanged');
$hkRates=allzone(1);
check(byl_dev_global_special_rates($hkRates,$ordinary)===$hkRates,
    'Hong Kong ordinary Free Shipping #2 unchanged');
$gb=package(['regular'=>item(37763,20),'special'=>item(32251,98)]);
check(byl_dev_global_special_packages([$gb])[0]===$gb,
    'Restworld existing special splitter remains authoritative');
$promo=[
    'active'=>true,'zone_id'=>5,'threshold'=>69,
    'subtotal'=>259,'remaining'=>0,'eligible'=>true,
    'unlocked'=>true,'state'=>'automatic_unlocked'
];
$cart=[
  'normal'=>item(37763,64),'special'=>item(32251,94)
];
$correct=byl_dev_global_special_promo($promo,$cart);
check($correct['subtotal']===64.0,
    'global promo uses discounted regular Woo line_total');
check($correct['remaining']===5.0,'global promo remaining correct');
check($correct['unlocked']===false && $correct['state']==='special_shipping_applies',
    'never call entire mixed cart free with $30 charged');
$only=byl_dev_global_special_promo($promo,['special'=>item(32251,98)]);
check($only['subtotal']===0.0 && $only['eligible']===false
    && $only['unlocked']===false,
    'special-only promo never promises ordinary free shipping');
foreach ([1,3,4] as $zone) {
    $regional=$promo;
    $regional['zone_id']=$zone;
    $regional['threshold']=$zone===1?0:($zone===3?169:129);
    $o=byl_dev_global_special_promo($regional,$cart);
    check($o['threshold']===$regional['threshold'],
        'existing threshold preserved zone '.$zone);
}
$untouched=byl_dev_global_special_promo(
    $promo,['ordinary'=>item(37763,80)]
);
check($untouched===$promo,'normal-only promotions unchanged');

$hkSpecialPackage=byl_dev_global_special_packages([
    allpackage(['special'=>item(9153,179)],1)
])[0];
$hkSpecialRates=byl_dev_global_special_rates(allzone(1),$hkSpecialPackage);
$hkCharge=current($hkSpecialRates);
check($hkCharge->get_method_id()==='byl_dev_global_special30_hk',
    'HK emergency method ID must follow verified Woo 11.2.1 constructor');
check($hkCharge->get_tax_status()==='taxable',
    'HK exception shipping tax status must use Woo default taxable');
check($hkCharge->get_taxes()===[],
    'HK exception calculates Woo shipping tax data (empty for test rates)');
$GLOBALS['cart_items']=[
    'regular'=>item(37763,64),'special'=>item(32251,94)
];
$specialPromotion=[
    'active'=>true,'zone_id'=>5,'threshold'=>69,
    'subtotal'=>158,'remaining'=>0,'eligible'=>true,
    'unlocked'=>true,'state'=>'automatic_unlocked'
];
$response=new TestRestResponse([
    'extensions'=>(object)[
        'byl-shipping-promotion'=>(object)$specialPromotion,
        'other-extension'=>(object)['other'=>'unchanged']
    ],
    'totals'=>['total_shipping'=>'3000','currency_code'=>'USD']
]);
byl_dev_global_special_store_response(
    $response,null,new TestRestRequest('/wc/store/v1/cart')
);
$respData=$response->get_data();
$thePromo=$respData['extensions']->{'byl-shipping-promotion'};
check($thePromo->state==='special_shipping_applies'
    && $thePromo->subtotal===64.0 && $thePromo->remaining===5.0,
    'Store API post-discount global special promotion corrected');
check($thePromo->unlocked===false && $thePromo->special_shipping_applies===true,
    'Store API must not promise free delivery for paid special item');
check($respData['totals']['total_shipping']==='3000',
    'Store API correction never modifies real Woo freight');
check($respData['extensions']->{'other-extension'}->other==='unchanged',
    'Store API correction does not disturb other extension');
$checkoutResponse=new TestRestResponse([
    'extensions'=>(object)['byl-shipping-promotion'=>(object)$specialPromotion]
]);
byl_dev_global_special_store_response(
    $checkoutResponse,null,new TestRestRequest('/wc/store/v1/checkout')
);
check($checkoutResponse->get_data()['extensions']
    ->{'byl-shipping-promotion'}->subtotal===158,
    'Checkout/payment REST not changed by this adapter');

$GLOBALS['host']='www.beyourlover.com';
check(!byl_dev_global_special_active(),'WWW never activates');
$GLOBALS['host']='dev.beyourlover.com';
$GLOBALS['globalSpecialEnabled']='no';
check(!byl_dev_global_special_active(),'separate global flag default OFF');
$inactiveRates=allzone(5);
check(byl_dev_global_special_rates($inactiveRates,
    allpackage(['special'=>item(9153,179)],5))===$inactiveRates,
    'disabled adapter leaves Woo rates unchanged');
echo "PASS Dev Global Special Shipping V1: $checks cumulative offline assertions, no Woo or WWW changes\n";
