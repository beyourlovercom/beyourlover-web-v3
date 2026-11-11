<?php
declare(strict_types=1);
define('ABSPATH', '/');
class WC_Data_Exception extends Exception { public function __construct($code,$message,$data=[]) {parent::__construct($message);} }
class WP_Error {
    public function __construct(public string $code, public string $message, public array $data = []) {}
}
$GLOBALS['is_admin'] = false;
$GLOBALS['is_admin_user'] = false;
function add_filter(...$args) {}
function add_action(...$args) {}
function is_admin() { return $GLOBALS['is_admin']; }
function current_user_can($cap) { return $GLOBALS['is_admin_user']; }
function wp_doing_ajax() { return $GLOBALS['ajax'] ?? false; }
function get_current_user_id() { return 10; }
function wc_get_logger() { return new class { public function warning(...$args) {} }; }
function wc_get_order($id) { return $GLOBALS['rest_order'] ?? null; }
class TestProduct { public function __construct(private bool $shipping){} public function needs_shipping(){return $this->shipping;} }
class TestItem { public function __construct(private TestProduct $product) {} public function get_product(){return $this->product;} }
class WC_Order {
    public function __construct(
        private array $changes = ['status'=>'completed'],
        private array $meta = [],
        private bool $requiresShipping = true,
        private string $status = 'processing'
    ) {}
    public function get_id(){ return 42; }
    public function get_type(){ return 'shop_order'; }
    public function get_changes(){return $this->changes;}
    public function get_items($type){return [new TestItem(new TestProduct($this->requiresShipping))];}
    public function get_meta($key,$single=true){return $this->meta[$key]??'';}
    public function get_status(){return $this->status;}
}
class MockRequest {
    public function __construct(private string $status) {}
    public function get_method(){return 'PUT';}
    public function get_route(){return '/wc/v3/orders/42';}
    public function get_param($name){return $this->status;}
}
require __DIR__.'/../byl-woo-completion-guard.php';
$failures = [];
function check(string $name, callable $call, bool $throws): void {
    global $failures;
    try {
        $call();
        if ($throws) {$failures[]=$name.' should have rejected';}
        else {echo "PASS: $name\n";}
    } catch (WC_Data_Exception $e) {
        if ($throws) {echo "PASS: $name\n";}
        else {$failures[]=$name.' unexpectedly blocked: '.$e->getMessage();}
    }
}
$proof = [
    '_aftership_tracking_items'=>[['tracking_number'=>'9400111899222345678901','slug'=>'usps']]
];
check('Physical order without tracking blocked', fn()=>BYL_Woo_Completion_Guard::checkBeforeSave(new WC_Order()), true);
check('API with genuine AfterShip package allowed', fn()=>BYL_Woo_Completion_Guard::checkBeforeSave(new WC_Order(meta:$proof)), false);
check('Legacy tracking without carrier blocked', fn()=>BYL_Woo_Completion_Guard::checkBeforeSave(new WC_Order(meta:['_aftership_tracking_number'=>'12345678'])), true);
check('Virtual-only order allowed', fn()=>BYL_Woo_Completion_Guard::checkBeforeSave(new WC_Order(requiresShipping:false)), false);
check('Unchanged completed order allowed', fn()=>BYL_Woo_Completion_Guard::checkBeforeSave(new WC_Order(changes:[])), false);
check('Processing update allowed', fn()=>BYL_Woo_Completion_Guard::checkBeforeSave(new WC_Order(changes:['status'=>'processing'])), false);
$GLOBALS['is_admin'] = true;
$GLOBALS['ajax'] = true;
check('Non-admin AJAX shortcut blocked', fn()=>BYL_Woo_Completion_Guard::checkBeforeSave(new WC_Order(meta:$proof)), true);
$GLOBALS['is_admin']=false; $GLOBALS['ajax']=false;
$actions=BYL_Woo_Completion_Guard::removeBulkComplete(['mark_completed'=>'yes','mark_processing'=>'yes']);
if (!isset($actions['mark_completed']) && isset($actions['mark_processing'])) {echo "PASS: Bulk completed hidden\n";}
else {$failures[]='bulk action';}
$quick=BYL_Woo_Completion_Guard::removeQuickComplete(['complete'=>'yes','processing'=>'yes'],new WC_Order());
if (!isset($quick['complete']) && isset($quick['processing'])) {echo "PASS: Quick completed hidden\n";}
else {$failures[]='quick action';}
$GLOBALS['rest_order']=new WC_Order();
$denial=BYL_Woo_Completion_Guard::rejectRestCompletionWithoutTracking(null, null,new MockRequest('completed'));
if ($denial instanceof WP_Error && $denial->code==='byl_no_tracking' && ($denial->data['status']??null)===409) {echo "PASS: REST preflight denies no tracking\n";}
else {$failures[]='REST preflight';}
$GLOBALS['rest_order']=new WC_Order(meta:$proof);
$allowed=BYL_Woo_Completion_Guard::rejectRestCompletionWithoutTracking(null, null,new MockRequest('completed'));
if ($allowed===null) {echo "PASS: REST preflight permits tracking\n";}
else {$failures[]='REST permits evidence';}
if ($failures) {fwrite(STDERR,implode("\n",$failures)."\n");exit(1);}
echo "ALL 10 P0 unit checks PASS\n";
