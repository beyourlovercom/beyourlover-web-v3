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
function add_action($name,$callback): void { $GLOBALS['hooks'][$name] = $callback; }
function wp_kses_post($value){return $value;}
function admin_url($route){return 'https://dev.beyourlover.com/wp-admin/'.$route;}
function wp_create_nonce($action){return 'test-only-nonce';}
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
        return [['destination'=>($GLOBALS['case']==='unavailable'
            ? ['country'=>'DE','state'=>'','postcode'=>'']
            : ['country'=>'US','state'=>'IA','postcode'=>'51503']),
            'contents'=>[],'contents_cost'=>$GLOBALS['shipping_total']]];
    }
    public function get_displayed_subtotal(){return $GLOBALS['shipping_total'];}
    public function get_cart(){
        if (!$GLOBALS['cart_count']) return [];
        return [['product_id'=>$GLOBALS['case']==='special'?32251:37763,
            'line_total'=>$GLOBALS['case']==='coupon'?60:$GLOBALS['shipping_total'],
            'quantity'=>1]];
    }
    public function get_applied_coupons(){return [];}
}
class WooSession {
    public function get($key, $default=[]){return $GLOBALS['case']==='selected' ? ['free_shipping:9'] : ['flat_rate:10'];}
}
class FreeMethod {
    public $enabled = 'yes';
    public function __construct(
        public string $id, private float $amount=0,
        private string $requires='', private float $cost=0
    ){}
    public function get_method_id(){return $this->id;}
    public function get_instance_option($key){
        return ['requires'=>$this->requires,
            'min_amount'=>(string)$this->amount,
            'ignore_discounts'=>'no',
            'cost'=>(string)$this->cost][$key]??null;
    }
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
class MockCountries {
    public function get_shipping_countries(){return ['HK'=>'Hong Kong','US'=>'United States','AE'=>'United Arab Emirates','MX'=>'Mexico','JP'=>'Japan','RU'=>'Russia','DE'=>'Germany'];}
    public function get_states($country){return ['IA'=>'Iowa','AK'=>'Alaska','HI'=>'Hawaii'];}
    public function get_continents(){return ['AS'=>['countries'=>['HK','AE','JP']]];}
}
class Woo {
    public $cart;public $session;public $ship;public $countries;
    public function __construct(){$this->cart=new WooCart();$this->session=new WooSession();$this->ship=new WooShipping();$this->countries=new MockCountries();}
    public function shipping(){return $this->ship;}
}
$GLOBALS['woo']=new Woo();
function WC(){return $GLOBALS['woo'];}
class Zone {
    public function __construct(private ?int $id=null){}
    public function get_id(){return $this->id ?? ($GLOBALS['case']==='unavailable' ? 0 : 5);}
}
class WooFallbackZone {
    public function get_shipping_methods($enabled_only=true){
        $methods=[new FreeMethod('flat_rate',0,'',20)];
        if ($GLOBALS['case']!=='unavailable') {
            $methods[]=new FreeMethod('free_shipping',69,'min_amount');
        }
        return $methods;
    }
}
class WC_Shipping_Zones {
    public static function get_zone($id){return $id===0?new WooFallbackZone():null;}
    public static function get_zone_matching_package($package){
        $destination=$package['destination']??[];
        $country=$destination['country']??'';
        $state=$destination['state']??'';
        if ($country==='HK')return new Zone(1);
        if ($country==='US' && ($state==='AK'||$state==='HI'))return new Zone(4);
        if ($country==='US' && $state==='IA')return new Zone(5);
        if ($country==='MX')return new Zone(4);
        if ($country==='AE'||$country==='JP')return new Zone(5);
        if ($country==='RU')return new Zone(3);
        if ($country==='DE')return new Zone(0);
        return new Zone();
    }
    public static function get_zones(){
        return [
            // Real Woo get_zones() excludes the fallback, which is fetched
            // through get_zone(0)->get_shipping_methods(true).
            1=>['shipping_methods'=>[new FreeMethod('free_shipping')]],
            3=>['shipping_methods'=>[new FreeMethod('free_shipping',169,'min_amount'),
                new FreeMethod('flat_rate',0,'',30)]],
            4=>['shipping_methods'=>[new FreeMethod('free_shipping',129,'min_amount'),
                new FreeMethod('flat_rate',0,'',20)]],
            5=>['shipping_methods'=>[new FreeMethod('free_shipping',69,'min_amount'),
                new FreeMethod('flat_rate',0,'',10)]],
        ];
    }
}
class Andy_Commerce_Shipping_Promotion_Policy {
    public static function get_persisted_settings(){return ['mode'=>'automatic','free_shipping_code'=>'BYL49'];}
    public static function has_persisted_settings(){return true;}
    public static function get_persisted_zone_effective_threshold($id){return [1=>0.0,5=>69.0,4=>129.0,3=>169.0,0=>49.0][$id]??null;}
    public static function normalize_code($s){return strtolower($s);}
}
require __DIR__.'/../theme/beyourlover-v3/inc/free-shipping-v2-policy.php';
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
$route = file_get_contents(__DIR__.'/../theme/beyourlover-v3/functions.php');
check(str_contains($route, "get_page_by_path('free-shipping-guide'"), 'page router has exact internal slug');
$assets = file_get_contents(__DIR__.'/../theme/beyourlover-v3/inc/theme-os-assets.php');
check(str_contains($assets, "is_page('free-shipping-guide')"), 'new page enqueues the approved CSS');
check(str_contains($route, "byl_v3_is_free_shipping_route()"), 'route is exact-path scoped');
check(str_contains($route, "permalink_manager_filter_redirect"), 'do not allow Permalink Manager to redirect the public Page');
check(is_file(__DIR__.'/../theme/beyourlover-v3/page-free-shipping-guide.php'), 'internal slug has proper template alias');
check(str_contains($below,'Check a destination'), 'destination chooser is present');
check(str_contains($below,'Free shipping by country'), 'country policy section is present');
check(str_contains($below,'data-shipping-zone="1"') && str_contains($below,'data-shipping-zone="5"')
    && str_contains($below,'data-shipping-zone="4"') && str_contains($below,'data-shipping-zone="3"')
    && str_contains($below,'data-shipping-zone="0"'), 'five Woo zones including fallback');
check(str_contains($below,'$129') && str_contains($below,'$169') && str_contains($below,'$69'), 'four thresholds from Woo policy');
check(str_contains($below,'Rest of the World fallback'), 'fallback country policy stated');
check(str_contains($below,'$20'), 'restworld paid shipping base appears');
check(str_contains($below,'$30 per pillow') && str_contains($below,'SAKURA'),
    'special-product exception policies stated');
check(!str_contains($below,'an order threshold of $49'), 'legacy global fallback is not falsely advertised');

check(byl_v3_fs_destination_policy('HK')['threshold']===0.0, 'Hong Kong zero minimum');
check(byl_v3_fs_destination_policy('US','IA')['threshold']===69.0, 'Iowa 69 minimum');
check(byl_v3_fs_destination_policy('US','AK')['threshold']===129.0, 'Alaska 129 minimum');
check(byl_v3_fs_destination_policy('AE')['threshold']===69.0, 'UAE actual Asia priority overrides configured 129 zone');
check(byl_v3_fs_destination_policy('MX')['threshold']===129.0, 'Mexico 129 minimum');
check(byl_v3_fs_destination_policy('RU')['threshold']===169.0, 'Russia 169 minimum');
check(byl_v3_fs_destination_policy('DE')['threshold']===69.0, 'Germany Zone0 actual native Woo free minimum69');
check(byl_v3_fs_destination_policy('DE')['base_flat_cost']===20.0, 'Germany Zone0 actual flat base20');
check(byl_v3_fs_destination_policy('DE')['basis']==='after_coupon', 'Zone0 after-coupon basis');
$GLOBALS['case']='unavailable';
check(byl_v3_fs_destination_policy('DE')['status']==='no_free_method',
    'Zone0 without Free method does not fabricate min69');
$GLOBALS['case']='below';
check(byl_v3_fs_destination_policy('US')['status']==='needs_state', 'US requires state');
check(byl_v3_fs_destination_policy('ZZ')['status']==='not_served', 'non-shipping country not served');
$coupon=render_case('coupon',80,1);
check(str_contains($coupon,'Add <strong>$9.00</strong> more for free shipping'),
    'page progress uses $60 discounted regular subtotal, not original $80');
$special=render_case('special',179,1);
check(str_contains($special,'Special items have separate shipping charges'),
    'special-only cart cannot celebrate free shipping despite high price');
check(!str_contains($special,"🎉 Congrats! You've unlocked free shipping."),
    'special-only cart has no false free shipping');
echo "PASS /free-shipping/ V3 — native 5-zone thresholds incl fallback, coupon and special exclusions\n";
