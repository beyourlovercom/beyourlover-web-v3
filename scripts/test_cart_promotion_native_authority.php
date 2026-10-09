<?php
/** No WP, DB, shipping writes or real orders: classic Woo cart promotion contracts. */
declare(strict_types=1);
define('ABSPATH', __DIR__);
$GLOBALS['hooks'] = [];
function add_action($hook,$callback,$priority=10){$GLOBALS['hooks'][$hook]=$callback;}
function is_cart(){return true;}
function wp_json_encode($v){return json_encode($v);}
function esc_attr($v){return htmlspecialchars((string)$v, ENT_QUOTES);}
function wc_get_price_decimals(){return 2;}
class FakeMethod {
    public function __construct(private string $id) {}
    public function get_method_id(){return $this->id;}
}
class FakeRate {
    public function __construct(private string $id, private float $cost) {}
    public function get_method_id(){return $this->id;}
    public function get_cost(){return $this->cost;}
}
class FakeZone {
    public function __construct(private int $zone=5, private bool $free=true){}
    public function get_id(){return $this->zone;}
    public function get_shipping_methods($enabled=true){return $this->free ? [new FakeMethod('flat_rate'),new FakeMethod('free_shipping')]:[new FakeMethod('flat_rate')];}
}
class WC_Shipping_Zones {
    public static $zone;
    public static function get_zone_matching_package($pkg){return self::$zone;}
}
class Andy_Commerce_Shipping_Promotion_Policy {
    public static function get_persisted_settings(){return ['free_shipping_code'=>'BYL49','mode'=>'automatic'];}
    public static function get_persisted_zone_effective_threshold($id){return 5===$id ? 69.0 : 0.0;}
    public static function normalize_code($value){return strtoupper((string)$value);}
}
class MockCart {
    public $subtotal = 59;
    public function get_shipping_packages(){return [['destination'=>['country'=>'US','state'=>'IA','postcode'=>'51503']]];}
    public function get_displayed_subtotal(){return $this->subtotal;}
    public function get_applied_coupons(){return [];}
}
class MockShipping {
    public $rates = [];
    public function get_packages(){return [['rates'=>$this->rates]];}
}
class MockSession {
    public $chosen = ['flat_rate:10'];
    public function get($key,$fallback=[]){return $this->chosen;}
}
class MockWoo { public $cart; public $session; public $shippingObj; public function __construct(){$this->cart=new MockCart();$this->session=new MockSession();$this->shippingObj=new MockShipping();} public function shipping(){return $this->shippingObj;} }
$GLOBALS['wc'] = new MockWoo();
function WC(){return $GLOBALS['wc'];}
require dirname(__DIR__).'/theme/beyourlover-v3/inc/cart-experience.php';
function tassert($ok,$name){if(!$ok){fwrite(STDERR,"FAIL $name\n");exit(1);}}
WC_Shipping_Zones::$zone = new FakeZone(5,true);
WC()->shipping()->rates = ['flat_rate:10'=>new FakeRate('flat_rate',10)];
$a = byl_v3_cart_native_promotion_state();
tassert($a['state']==='below_threshold' && $a['threshold']===69.0 && $a['remaining']===10.0,'Iowa $59/$69 $10 due');
tassert(!$a['unlocked'] && $a['zone_id']===5,'not unlocked with $10 flat');
ob_start();byl_v3_render_native_cart_promotion_context();$markup=ob_get_clean();
tassert(str_contains($markup,'data-byl-native-promotion') && str_contains($markup,'&quot;remaining&quot;:10'),'native context rendered in Woo totals');
WC()->cart->subtotal=79;
WC()->shipping()->rates['free_shipping:9'] = new FakeRate('free_shipping',0);
$a=byl_v3_cart_native_promotion_state();
tassert($a['state']==='free_shipping_available' && !$a['unlocked'],'paid selected even though free option exists');
WC()->session->chosen=['free_shipping:9'];
$a=byl_v3_cart_native_promotion_state();
tassert($a['state']==='automatic_unlocked' && $a['unlocked'],'free selected and zero cost');
WC_Shipping_Zones::$zone=new FakeZone(0,false);
WC()->cart->subtotal=59;
$a=byl_v3_cart_native_promotion_state();
tassert($a['state']==='shipping_unavailable','zone without free method must not promise free');
echo "PASS cart native Woo shipping-zone authority ($59/$69, $10 flat, free selected, missing free method)\n";
