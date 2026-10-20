<?php
/** Dev-only after-coupon shipping: offline no-network money/WWW regression. */
declare(strict_types=1);
define('ABSPATH', __DIR__);
$GLOBALS['test_host']='dev.beyourlover.com';
$GLOBALS['test_environment']='staging';
$GLOBALS['test_wc_cart']=null;
$GLOBALS['test_filters']=[];
function home_url($path='/'){return 'https://' . $GLOBALS['test_host'] . $path;}
function wp_parse_url($url,$component){return parse_url($url,$component);}
function wp_get_environment_type(){return $GLOBALS['test_environment'];}
function WC(){return (object)['cart'=>$GLOBALS['test_wc_cart']];}
function add_action($hook,$callback,$priority=10){$GLOBALS['test_filters'][$hook]=[$callback,$priority];}
function add_filter($hook,$callback,$priority=10,$arity=1){$GLOBALS['test_filters'][$hook]=[$callback,$priority,$arity];}
function wc_get_price_decimals(){return 2;}
function get_option($key,$fallback=[]){
    return match($key){
        'woocommerce_free_shipping_9_settings'=>['requires'=>'min_amount','min_amount'=>'69'],
        'woocommerce_free_shipping_11_settings'=>['requires'=>'min_amount','min_amount'=>'129'],
        'woocommerce_free_shipping_12_settings'=>['requires'=>'coupon','min_amount'=>'69'],
        'woocommerce_free_shipping_13_settings'=>['requires'=>'both','min_amount'=>'69'],
        default=>$fallback
    };
}
class DevCart {
    public function __construct(public array $items,public array $coupons=[]){}
    public function get_cart(){return $this->items;}
    public function get_applied_coupons(){return $this->coupons;}
}
class DevRate {
    public function __construct(private string $id,private int $instance=0){}
    public function get_method_id(){return $this->id;}
    public function get_instance_id(){return $this->instance;}
}
class DevResponse {
    public function __construct(private array $data){}
    public function get_data(){return $this->data;}
    public function set_data($next){$this->data=$next;}
}
class DevRequest {
    public function __construct(private string $route){}
    public function get_route(){return $this->route;}
}
require dirname(__DIR__) . '/theme/beyourlover-v3/inc/dev-after-discount-free-shipping.php';
$checks=0;
function insist($condition,string $why): void {
    global $checks;
    if (!$condition){fwrite(STDERR,"FAIL after-discount shipping: $why\n");exit(1);}
    $checks++;
}
$original=['product_id'=>25447,'quantity'=>1,'line_total'=>96];
$discounted=['product_id'=>25447,'quantity'=>1,'line_total'=>66];
$pillow=['product_id'=>38047,'variation_id'=>38165,'quantity'=>1,'line_total'=>59];
$free9=new DevRate('free_shipping',9);
$flat=new DevRate('flat_rate',10);
$rates=['free_shipping:9'=>$free9,'flat_rate:10'=>$flat];
$GLOBALS['test_wc_cart']=new DevCart(['sku'=>$discounted],['valid-30-coupon']);
insist(byl_dev_after_discount_enabled(),'Dev exact staging allowed');
byl_dev_after_discount_boot();
insist($GLOBALS['test_filters']['woocommerce_package_rates'][1]===100000,'late rate registration');
insist($GLOBALS['test_filters']['rest_post_dispatch'][1]===10000,'late REST registration');
insist(byl_dev_after_discount_subtotal(['sku'=>$discounted])===66.0,'after-coupon line total $66');
$blocked=byl_dev_after_discount_rates($rates,['contents'=>['sku'=>$discounted]]);
insist(!isset($blocked['free_shipping:9'])&&isset($blocked['flat_rate:10']),'$66 excludes normal free $69');
$pass=byl_dev_after_discount_rates($rates,['contents'=>['sku'=>$original]]);
insist(isset($pass['free_shipping:9']),'$96 maintains free');
foreach([68.99=>false,69=>true,70=>true] as $sum=>$eligible){
    $item=['product_id'=>25447,'line_total'=>(float)$sum];
    $out=byl_dev_after_discount_rates($rates,['contents'=>['sku'=>$item]]);
    insist(isset($out['free_shipping:9'])===$eligible, "threshold exact $sum");
}
insist(byl_dev_after_discount_rate_minimum(new DevRate('free_shipping',12))===null,'coupon-only rate exempt');
insist(byl_dev_after_discount_rate_minimum(new DevRate('free_shipping',13))===69.0,'both rule requires min');
insist(byl_dev_after_discount_rate_minimum(new DevRate('free_shipping',11))===129.0,'regional threshold 129');
$other=['free_shipping:11'=>new DevRate('free_shipping',11),'flat_rate:10'=>$flat];
insist(!isset(byl_dev_after_discount_rates($other,['contents'=>['sku'=>['product_id'=>25447,'line_total'=>120]]])['free_shipping:11']),'regional $120 below $129');
$withPillow=['pillow'=>$pillow,'normal'=>$discounted];
$GLOBALS['test_wc_cart']=new DevCart($withPillow,['valid-30-coupon']);
insist(byl_dev_after_discount_rates($rates,['contents'=>['normal'=>$discounted],'byl_dev_pillow_kind'=>'ordinary'])===$rates,'mixed cart delegated to PR150');
insist(byl_dev_after_discount_rates($rates,['contents'=>['pillow'=>$pillow],'byl_dev_pillow_kind'=>'pillow'])===$rates,'pillow-only untouched');
$GLOBALS['test_wc_cart']=new DevCart(['sku'=>$discounted],['byl49']);
insist(byl_dev_after_discount_rates($rates,['contents'=>['sku'=>$discounted]])===$rates,'BYL49 special coupon explicitly held');
$GLOBALS['test_wc_cart']=new DevCart(['sku'=>$discounted],['valid-30-coupon']);
$promo=['active'=>true,'mode'=>'automatic','threshold'=>69,'subtotal'=>96,'remaining'=>0,'eligible'=>true,'unlocked'=>true,'state'=>'automatic_code','applied_code'=>'valid-30-coupon'];
$corrected=byl_dev_after_discount_promo_correct($promo,['sku'=>$discounted]);
insist($corrected['subtotal']===66.0 && $corrected['remaining']===3.0,'REST promo after coupon is $66/$3 remaining');
insist($corrected['eligible']===false && $corrected['unlocked']===false && $corrected['state']==='below_threshold','REST not falsely free');
insist(byl_dev_after_discount_promo_correct($promo,['sku'=>$original])['unlocked']===true,'normal $96 remains free');
$nested=new DevResponse(['extensions'=>(object)['byl-shipping-promotion'=>(object)$promo,'another'=>(object)['ok'=>true]],'totals'=>['total_price'=>'6600','total_shipping'=>'0']]);
byl_dev_after_discount_rest($nested,null,new DevRequest('/wc/store/v1/cart'));
$r=$nested->get_data();
insist($r['extensions'] instanceof stdClass,'preserve Woo object registry');
insist($r['extensions']->{'byl-shipping-promotion'} instanceof stdClass,'preserve Woo object payload');
insist($r['extensions']->{'byl-shipping-promotion'}->subtotal===66.0,'actual object response $66');
insist($r['extensions']->another->ok ===true,'other extension untouched');
insist($r['totals']['total_price']==='6600','no actual Woo money mutation');
$checkout=new DevResponse(['extensions'=>(object)['byl-shipping-promotion'=>(object)$promo]]);
byl_dev_after_discount_rest($checkout,null,new DevRequest('/wc/store/v1/checkout'));
insist($checkout->get_data()['extensions']->{'byl-shipping-promotion'}->subtotal===96,'Checkout endpoint not altered');
$GLOBALS['test_wc_cart']=new DevCart($withPillow,['valid-30-coupon']);
insist(byl_dev_after_discount_promo_correct($promo,$withPillow)===$promo,'mixed PR150 metadata untouched');
$GLOBALS['test_wc_cart']=new DevCart(['sku'=>$discounted],['byl49']);
insist(byl_dev_after_discount_promo_correct($promo,['sku'=>$discounted])===$promo,'BYL49 metadata unchanged until Owner acceptance');
foreach([['www.beyourlover.com','staging'],['dev.beyourlover.com','production'],['www.beyourlover.com','production']] as $case){
    $GLOBALS['test_host']=$case[0];$GLOBALS['test_environment']=$case[1];
    insist(!byl_dev_after_discount_enabled(),'never active outside exact Dev staging');
    insist(byl_dev_after_discount_rates($rates,['contents'=>['sku'=>$discounted]])===$rates,'WWW / production rates untouched');
}
echo "PASS Dev post-discount Free Shipping V1: $checks offline assertions; WWW disabled; no orders or database operations\n";
