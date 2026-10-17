<?php
/**
 * Isolated classic-Woo checkout address contract tests.
 * NO WordPress boot, order storage, Woo POST, customer DB or payment.
 */
define('ABSPATH', '/isolated/');
$GLOBALS['hooks'] = []; $GLOBALS['logged'] = false; $GLOBALS['shipping_saved'] = [];
function add_filter($k,$f,$p=10,$n=1){$GLOBALS['hooks'][$k][]=$f;}
function add_action($k,$f,$p=10,$n=1){$GLOBALS['hooks'][$k][]=$f;}
function is_user_logged_in(){return $GLOBALS['logged'];}
function get_current_user_id(){return $GLOBALS['logged']?101:0;}
function wc_clean($x){return is_array($x)?array_map('wc_clean',$x):trim((string)$x);}
function wp_unslash($x){return $x;}
function sanitize_text_field($x){return trim(strip_tags((string)$x));}
function __($s,$domain){return $s;}
class ErrorBag {
  public $list=[];
  public function add($key,$msg){$this->list[$key]=$msg;}
}
class WC_Validation {
  public static function is_postcode($p,$c){return strlen((string)$p)>2 && (bool) preg_match('/^[A-Za-z0-9 -]+$/',(string)$p);}
}
class WC_Countries {
  public function get_allowed_countries(){return ['US'=>'United States','MX'=>'Mexico','DE'=>'Germany'];}
  public function get_states($c){return $c==='US'?['CA'=>'California','NY'=>'New York']:[];}
}
function WC(){return (object)['countries'=>new WC_Countries()];}
class WC_Order {
  public $fields=[];
  public function __call($n,$params) {
    if(strpos($n,'set_')===0){$this->fields[substr($n,4)]=$params[0];return;}
    throw new RuntimeException('Bad order method:'.$n);
  }
}
class WC_Customer {
  public $fields=[];
  public function __construct($id=null,$autoload=false){$this->fields=$GLOBALS['shipping_saved'];}
  public function __call($n,$params) {
    if(strpos($n,'set_')===0){$this->fields[substr($n,4)]=$params[0];return;}
    if(strpos($n,'get_')===0){return $this->fields[substr($n,4)]??'';}
    throw new RuntimeException('Bad customer method:'.$n);
  }
}
require __DIR__.'/../theme/beyourlover-v3/inc/checkout-address-contract.php';
$calls=0;
function ok($good,$message) {
 global $calls;if(!$good)throw new RuntimeException('FAIL '.$message);$calls++;
}
ok(count($GLOBALS['hooks']['woocommerce_checkout_posted_data']??[])===1,'POST filter');
ok(count($GLOBALS['hooks']['woocommerce_checkout_create_order']??[])===1,'order CRUD hook');
ok(count($GLOBALS['hooks']['woocommerce_checkout_update_customer_data']??[])===1,'account update gate');
ok(count($GLOBALS['hooks']['woocommerce_checkout_update_customer']??[])===1,'user Woo update');
ok(count($GLOBALS['hooks']['woocommerce_after_checkout_validation']??[])===1,'invoice validation');
ok(count($GLOBALS['hooks']['woocommerce_checkout_get_value']??[])===1,'shipping address prefill');
$post=$GLOBALS['hooks']['woocommerce_checkout_posted_data'][0];
$validate=$GLOBALS['hooks']['woocommerce_after_checkout_validation'][0];
$order_save=$GLOBALS['hooks']['woocommerce_checkout_create_order'][0];
$should_save=$GLOBALS['hooks']['woocommerce_checkout_update_customer_data'][0];
$user_save=$GLOBALS['hooks']['woocommerce_checkout_update_customer'][0];
$prefill=$GLOBALS['hooks']['woocommerce_checkout_get_value'][0];
$ship=[
 'billing_first_name'=>'Mandy','billing_last_name'=>'Sva','billing_company'=>'BYL Ltd',
 'billing_country'=>'MX','billing_address_1'=>'Av. Reforma 296','billing_address_2'=>'Apt 3',
 'billing_city'=>'Mexico City','billing_state'=>'CMX','billing_postcode'=>'06600',
 'billing_email'=>'test@example.test','shipping_phone'=>'+525512345678'
];
$GLOBALS['logged']=false;
$_POST=['byl_use_shipping_for_billing'=>'1','byl_save_address'=>'1','byl_invoice_country'=>'US'];
$data=$post($ship);
ok($data['byl_save_address']===0,'guest cannot save even forged checkbox');
ok(!byl_v3_checkout_separate_billing($data),'default same billing');
ok($should_save(true)===false,'guest no WC Customer saved');
$order=new WC_Order();$order_save($order,$data);
ok($order->fields['shipping_country']==='MX','shipping country true');
ok($order->fields['shipping_address_1']==='Av. Reforma 296','shipping street true');
ok(!isset($order->fields['billing_country']),'same-billing leaves WC native billing intact');
ok($order->fields['shipping_phone']==='+525512345678','shipping phone');
$user=new WC_Customer();$user_save($user,$data);
ok($user->fields===[],'guest update hook cannot persist');

$invoice=[
 'byl_invoice_first_name'=>'Anne','byl_invoice_last_name'=>'Buyer',
 'byl_invoice_company'=>'Accounts Co','byl_invoice_country'=>'US',
 'byl_invoice_address_1'=>'22 Billing Ave','byl_invoice_address_2'=>'Suite 10',
 'byl_invoice_city'=>'Los Angeles','byl_invoice_state'=>'CA','byl_invoice_postcode'=>'90210'
];
$GLOBALS['logged']=true;
$_POST=array_merge(['byl_use_shipping_for_billing'=>'0','byl_save_address'=>'1'],$invoice);
$data=$post($ship);
ok(byl_v3_checkout_separate_billing($data),'separate billing parsed');
ok($data['byl_save_address']===1,'logged explicit save parsed');
$errors=new ErrorBag();$validate($data,$errors);
ok(!$errors->list,'valid invoice accepted');
$order=new WC_Order();$order_save($order,$data);
ok($order->fields['shipping_country']==='MX','MX shipping retained');
ok($order->fields['billing_country']==='US','US billing independent');
ok($order->fields['billing_state']==='CA','invoice state saved');
ok($order->fields['billing_company']==='Accounts Co','invoice company saved');
ok($order->fields['shipping_company']==='BYL Ltd','shipping organization retained');
ok($order->fields['billing_postcode']==='90210','invoice postcode');
ok($should_save(true)===true,'logged explicit account opt-in');
$user=new WC_Customer();$user_save($user,$data);
ok($user->fields['shipping_address_1']==='Av. Reforma 296','saved actual shipping');
ok($user->fields['billing_address_1']==='22 Billing Ave','saved separate billing');
ok($user->fields['shipping_country']==='MX'&&$user->fields['billing_country']==='US','saved address countries independent');
ok($user->fields['shipping_phone']==='+525512345678','saved shipping phone');

$_POST=['byl_use_shipping_for_billing'=>'0','byl_save_address'=>'0','byl_invoice_country'=>'US'];
$data=$post($ship);
$errors=new ErrorBag();$validate($data,$errors);
ok(count($errors->list)>=5,'incomplete invoice blocked');
ok(isset($errors->list['byl_invoice_state_invalid']),'state required from verified WC states');
ok($should_save(true)===false,'opted-out customer untouched');
ok($should_save(false)===false,'respect upstream Woo false');
$_POST=array_merge(['byl_use_shipping_for_billing'=>'0','byl_save_address'=>'0'],$invoice);
$_POST['byl_invoice_country']='FR';
$data=$post($ship);$errors=new ErrorBag();$validate($data,$errors);
ok(isset($errors->list['byl_invoice_country_invalid']),'unsupported country blocked');
$_POST['byl_invoice_country']='US';$_POST['byl_invoice_state']='ZZ';
$data=$post($ship);$errors=new ErrorBag();$validate($data,$errors);
ok(isset($errors->list['byl_invoice_state_invalid']),'invalid province blocked');
$_POST['byl_invoice_state']='CA';$_POST['byl_invoice_postcode']='!!';
$data=$post($ship);$errors=new ErrorBag();$validate($data,$errors);
ok(isset($errors->list['byl_invoice_postcode_invalid']),'postcode validator invoked');
$GLOBALS['shipping_saved']=['shipping_address_1'=>'Saved shipping street','shipping_country'=>'MX','billing_country'=>'US'];
ok($prefill(null,'billing_address_1')==='Saved shipping street','shipping prefills from real account shipping');
ok($prefill(null,'billing_country')==='MX','delivery country not invoice country');
ok($prefill(null,'billing_email')===null,'email untouched');
ok($prefill('existing','billing_address_1')==='existing','upstream Woo value preserved');
$GLOBALS['logged']=false;
ok($prefill(null,'billing_address_1')===null,'guest never reads saved account');
echo 'PASS Checkout real Shipping/Billing/Save Address: '.$calls.' isolated Woo stub assertions; no DB/order/mail writes'.PHP_EOL;
