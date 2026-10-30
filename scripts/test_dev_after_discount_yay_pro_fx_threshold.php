<?php
/** Offline Dev theme/YayCurrency Pro forward-FX threshold contract. */
declare(strict_types=1);
require __DIR__ . '/test_dev_after_discount_free_shipping_v1.php';

class SyntheticYayPro {
    public static string $currency='USD';
    public static function detect_current_currency(): array {
        return ['status'=>1,'currency'=>self::$currency];
    }
}
class_alias(SyntheticYayPro::class, 'Yay_Currency\\Helpers\\YayCurrencyHelper');
$GLOBALS['fxRate']=1.0;
$GLOBALS['fxAvailable']=true;
function has_filter($hook) {
    return $hook==='yay_currency_convert_price' && $GLOBALS['fxAvailable'] ? 10 : false;
}
function apply_filters($hook,$v) {
    if($hook!=='yay_currency_convert_price')throw new RuntimeException('untrusted converter');
    return number_format((float)$v*$GLOBALS['fxRate'],2,'.','');
}
class SyntheticWooProduct {
    public function __construct(private float $base) {}
    public function get_price(string $context='view') {
        return $context==='edit'
            ? number_format($this->base,2,'.','')
            : number_format($this->base*$GLOBALS['fxRate'],2,'.','');
    }
}
$GLOBALS['test_host']='dev.beyourlover.com';
$GLOBALS['test_environment']='staging';
$fx=['USD'=>1.0,'EUR'=>.897,'GBP'=>.7505,'AUD'=>1.4187,'CAD'=>1.4];
$checks_fx=0;
$checkfx=static function($condition,string $msg) use (&$checks_fx){
    if(!$condition){fwrite(STDERR,"FAIL FX forward threshold: $msg\n");exit(1);}
    $checks_fx++;
};
$rates_fx=['free_shipping:9'=>new DevRate('free_shipping',9),'flat_rate:10'=>new DevRate('flat_rate',10)];
foreach($fx as $code=>$rate) {
    SyntheticYayPro::$currency=$code;
    $GLOBALS['fxRate']=$rate;
    $min=round(69*$rate,2);
    $checkfx(abs((byl_dev_after_discount_display_threshold(69)??-1)-$min)<.001,"$code display min");
    foreach([60,75] as $source) {
        $items=[];
        foreach(($source===60?[60]:[60,15]) as $n=>$usd) {
            $product=new SyntheticWooProduct((float)$usd);
            $items["id$n"]=[
                'product_id'=>40028+$n,'quantity'=>1,'data'=>$product,
                'line_total'=>round($usd*$rate,2)
            ];
        }
        $GLOBALS['test_wc_cart']=new DevCart($items,[]);
        $result=byl_dev_after_discount_rates($rates_fx,['contents'=>$items]);
        $checkfx(isset($result['free_shipping:9'])===($source>=69),"$code native quote at source USD $source");
        $originalPromo=['active'=>true,'mode'=>'automatic','threshold'=>69,
            'subtotal'=>0,'remaining'=>0,'eligible'=>false,'unlocked'=>false,'state'=>'below_threshold'];
        $corrected=byl_dev_after_discount_promo_correct($originalPromo,$items);
        $checkfx(abs($corrected['threshold']-$min)<.001,"$code legacy progress min");
        $checkfx($corrected['eligible']===($source>=69),"$code legacy progress eligibility");
        $newPromo=$originalPromo;
        $newPromo['threshold']=$min;
        $newPromo['basis_currency']='USD';
        $newPromo['display_currency']=$code;
        $updated=byl_dev_after_discount_promo_correct($newPromo,$items);
        $checkfx(abs($updated['threshold']-$min)<.001,"$code new progress not double-FX");
    }
    // Real post-coupon line totals are in the visitor currency, not USD.
    foreach([[65,false],[72,true]] as [$discountedUSD,$expectFree]) {
        $items=['discounted'=>[
            'product_id'=>40028,'quantity'=>1,'data'=>new SyntheticWooProduct(75.0),
            'line_total'=>round($discountedUSD*$rate,2)
        ]];
        $GLOBALS['test_wc_cart']=new DevCart($items,['TEST-COUPON']);
        $result=byl_dev_after_discount_rates($rates_fx,['contents'=>$items]);
        $checkfx(isset($result['free_shipping:9'])===$expectFree,
            "$code coupon-adjusted USD $discountedUSD");
    }
    // Preserve other-zone minimum (129 USD) in all currencies.
    $zoneRates=['free_shipping:11'=>new DevRate('free_shipping',11),'flat_rate:10'=>new DevRate('flat_rate',10)];
    foreach([[120,false],[140,true]] as [$source,$expected]){
        $items=['zone'=>['product_id'=>40028,'quantity'=>1,
            'data'=>new SyntheticWooProduct((float)$source),
            'line_total'=>round($source*$rate,2)]];
        $GLOBALS['test_wc_cart']=new DevCart($items,[]);
        $checkfx(isset(byl_dev_after_discount_rates($zoneRates,['contents'=>$items])['free_shipping:11'])===$expected,
            "$code zone 129 source USD $source");
    }
}
// GBP 68.99 and 69.00 can both round to GBP51.78: must not grant 68.99.
SyntheticYayPro::$currency='GBP';
$GLOBALS['fxRate']=.7505;
foreach([[68.99,false],[69.0,true]] as [$source,$allowed]){
    $item=['product_id'=>40028,'quantity'=>1,'data'=>new SyntheticWooProduct($source),
        'line_total'=>round($source*.7505,2)];
    $contents=['edge'=>$item];
    $GLOBALS['test_wc_cart']=new DevCart($contents,[]);
    $checkfx(isset(byl_dev_after_discount_rates($rates_fx,['contents'=>$contents])['free_shipping:9'])===$allowed,
        "GBP rounding collision USD $source");
}
$GLOBALS['fxAvailable']=false;
$items=['id'=>['product_id'=>40028,'quantity'=>1,'data'=>new SyntheticWooProduct(75.0),'line_total'=>56.29]];
$GLOBALS['test_wc_cart']=new DevCart($items,[]);
$checkfx(!isset(byl_dev_after_discount_rates($rates_fx,['contents'=>$items])['free_shipping:9']),
    'missing official forward converter fails closed');
$GLOBALS['fxAvailable']=true;
SyntheticYayPro::$currency='JPY';
$checkfx(!isset(byl_dev_after_discount_rates($rates_fx,['contents'=>$items])['free_shipping:9']),
    'unsupported visitor currency fails closed');
SyntheticYayPro::$currency='EUR';
$GLOBALS['test_wc_cart']=new DevCart($items,['byl49']);
$checkfx(byl_dev_after_discount_rates($rates_fx,['contents'=>$items])===$rates_fx,'BYL49 delegated unchanged');
$pillow=['product_id'=>38047,'quantity'=>1,'line_total'=>50];
$GLOBALS['test_wc_cart']=new DevCart(['pillow'=>$pillow],[]);
$checkfx(byl_dev_after_discount_rates($rates_fx,['contents'=>['pillow'=>$pillow]])===$rates_fx,'pillow delegated unchanged');
$GLOBALS['test_host']='www.beyourlover.com';
$checkfx(byl_dev_after_discount_rates($rates_fx,['contents'=>$items])===$rates_fx,'WWW never affected');
echo "PASS Dev after-discount YayCurrency forward-FX shipping: $checks_fx assertions, all five currencies, coupons, zone129, GBP boundary, unsupported/no converter, pillow, BYL49, WWW OFF\n";
