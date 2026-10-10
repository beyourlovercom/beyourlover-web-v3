<?php
/**
 * Standalone read-only /free-shipping/ page regression.
 * Runs without WP, Woo DB, orders or outbound traffic.
 */
declare(strict_types=1);
define('ABSPATH',__DIR__);
$GLOBALS['case'] = 'empty';
$GLOBALS['cart_count'] = 0;
$GLOBALS['shipping_total'] = 0;
function nocache_headers(): void { $GLOBALS['nocache_called'] = true; }
function get_woocommerce_currency_symbol() { return '$'; }
function wc_get_price_decimals(){return 2;}
function get_header(): void {}
function get_footer(): void {}
function home_url($path='/'){return 'https://dev.beyourlover.com'.$path;}
function wc_get_cart_url(){return 'https://dev.beyourlover.com/cart/';}
function esc_url($s){return htmlspecialchars($s, ENT_QUOTES);}
function esc_html($s){return htmlspecialchars((string)$s, ENT_QUOTES);}
function esc_attr($s){return htmlspecialchars((string)$s, ENT_QUOTES);}
class WooCart {
    public function get_cart_contents_count(){return $GLOBALS['cart_count'];}
    public function get_shipping_packages(){
        return [['destination'=>['country'=>'US','state'=>'IA','postcode'=>'51503'],'contents'=>[],'contents_cost'=>$GLOBALS['shipping_total']]];
    }
    public function get_displayed_subtotal(){return $GLOBALS['shipping_total'];}
    public function get_applied_coupons(){return [];}
}
class WooSession {
    public function get($key, $default=[]){return $GLOBALS['case']==='selected' ? ['free_shipping:9'] : ['flat_rate:10'];}
}
class FreeMethod {
    public $enabled = 'yes';
    public function __construct(public string $id){}
    public function get_method_id(){return $this->id;}
}
class ShippingRate {
    public function __construct(private string $method,private float $cost){}
    public function get_method_id(){return $this->method;}
    public function get_cost(){return $this->cost;}
}
class WooShipping {
    public function get_packages(){
        if ($GLOBALS['case']==='unavailable') return [['rates'=>['flat_rate:10'=>new ShippingRate('flat_rate',10)]]];
        return [['rates'=>['flat_rate:10'=>new ShippingRate('flat_rate',10),'free_shipping:9'=>new ShippingRate('free_shipping',0)]]];
    }
}
class Woo {
    public $cart;public $session;public $ship;
    public function __construct(){$this->cart=new WooCart();$this->session=new WooSession();$this->ship=new WooShipping();}
    public function shipping(){return $this->ship;}
}
$GLOBALS['woo']=new Woo();
function WC(){return $GLOBALS['woo'];}
class Zone { public function get_id(){return $GLOBALS['case']==='unavailable' ? 0 : 5;} }
class WC_Shipping_Zones {
    public static function get_zone_matching_package($package){return new Zone();}
    public static function get_zones(){
        return [5=>['shipping_methods'=>[new FreeMethod('free_shipping'),new FreeMethod('flat_rate')]]];
    }
}
class Andy_Commerce_Shipping_Promotion_Policy {
    public static function get_persisted_settings(){return ['mode'=>'automatic','free_shipping_code'=>'BYL49'];}
    public static function get_persisted_zone_effective_threshold($id){return $id===5?69.0:49.0;}
    public static function normalize_code($s){return strtolower($s);}
}
function check($yes,$label){if(!$yes){fwrite(STDERR,"FAIL $label\n");exit(1);}}
function render_case(string $name,float $amount,int $count):string{
    $GLOBALS['case']=$name;$GLOBALS['cart_count']=$count;$GLOBALS['shipping_total']=$amount;
    ob_start();
    include __DIR__.'/../theme/beyourlover-v3/page-free-shipping.php';
    return ob_get_clean();
}
$empty=render_case('empty',0,0);
check(str_contains($empty,'Your cart is empty.'),'empty cart is honest');
check(!str_contains($empty,'Add <strong>$69.00'),'empty cart never invents goal');
$below=render_case('below',59,1);
check(str_contains($below,'Free shipping on orders over $69'),'shipping zone 5 threshold');
check(str_contains($below,'Add <strong>$10.00</strong> more for free shipping'),'below goal');
check(str_contains($below,'aria-valuenow="86"'),'progress bar');
$at=render_case('at',69,1);
check(str_contains($at,"🎉 Congrats! You've unlocked free shipping."),'at-threshold congratulations');
check(str_contains($at,'Select the Free Shipping delivery method'),'paid choice is disclosed');
$selected=render_case('selected',89,1);
check(str_contains($selected,'Free shipping is selected'),'selected free shipping');
$none=render_case('unavailable',89,1);
check(str_contains($none,"Free shipping isn't available for this delivery zone"),'unsupported zone must not celebrate');
check(!str_contains($none,"🎉 Congrats! You've unlocked free shipping."),'no false promise');
check(!empty($GLOBALS['nocache_called']),'cart-dependent HTML must prevent caching');
check(str_contains($below,'Frequently asked questions') && str_contains($below,'Good to know'),'copy sections');
echo "PASS /free-shipping/ — empty, $59/$69, >=69, selected/unavailable, no-cache\n";
