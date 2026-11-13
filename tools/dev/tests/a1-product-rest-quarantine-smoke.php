<?php
/** Dev-only A1 Woo external products REST isolation contract. Source-only. */
error_reporting(E_ALL);
define('ABSPATH', __DIR__);
define('LOGGED_IN_COOKIE', 'wordpress_logged_in_fake_test');
$GLOBALS['a1_test'] = [
    'env' => 'staging', 'origin' => 'https://dev.beyourlover.com',
    'admin' => false, 'logged' => false, 'nonce' => false,
    'filter' => null,
];

class WP_REST_Request {
    public function __construct(private string $route, private string $nonce = '') {}
    public function get_route(): string { return $this->route; }
    public function get_header($name) {
        return strtolower($name)==='x-wp-nonce' ? $this->nonce : null;
    }
}
class WP_Error {
    public function __construct(public string $code, public string $message, public array $data) {}
}
function wp_get_environment_type() { return $GLOBALS['a1_test']['env']; }
function home_url() { return $GLOBALS['a1_test']['origin']; }
function untrailingslashit($v) { return rtrim($v, '/'); }
function is_user_logged_in() { return $GLOBALS['a1_test']['logged']; }
function current_user_can($cap) {
    return $cap === 'manage_woocommerce' && $GLOBALS['a1_test']['admin'];
}
function wp_verify_nonce($nonce, $action) {
    return $GLOBALS['a1_test']['nonce'] && $nonce === 'valid-nonce-123'
        && $action === 'wp_rest' ? 1 : false;
}
function add_filter($hook,$callback,$priority,$argc) {
    if ($hook==='rest_pre_dispatch' && $priority===96 && $argc===3) {
        $GLOBALS['a1_test']['filter']=$callback;
    }
}
require __DIR__ . '/../../../dev/mu-plugins/byl-a1-external-product-rest-isolation.php';

function a1_assert($ok,$message) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}
function call_a1($route,$response=null,$nonce='',$source='request') {
    return ($GLOBALS['a1_test']['filter'])(
        $response, new stdClass(), $source==='request'
            ? new WP_REST_Request($route,$nonce) : new stdClass()
    );
}
function reset_a1() {
    $GLOBALS['a1_test']['env']='staging';
    $GLOBALS['a1_test']['origin']='https://dev.beyourlover.com';
    $GLOBALS['a1_test']['admin']=false;
    $GLOBALS['a1_test']['logged']=false;
    $GLOBALS['a1_test']['nonce']=false;
    unset($_COOKIE[LOGGED_IN_COOKIE]);
}
a1_assert(is_callable($GLOBALS['a1_test']['filter']),'filter registered');
$checks=0;
foreach (['/wc/v3/products', '/wc/v2/products/35',
          '/wc/v1/products/35/variations', '/wc/v3/products/categories'] as $route) {
    reset_a1();
    $v=call_a1($route);
    a1_assert($v instanceof WP_Error && $v->data['status']===403 &&
              $v->code==='byl_a1_dev_external_product_rest_quarantined',
              "external route denied {$route}");
    $checks++;
}
foreach (['/wc/store/v1/products','/wc/v3/orders',
          '/wp/v2/posts','/wc/v3/productsXYZ'] as $route) {
    reset_a1();
    a1_assert(call_a1($route)===null,"unrelated route unchanged {$route}");
    $checks++;
}
reset_a1();
$GLOBALS['a1_test']['admin']=true;
$GLOBALS['a1_test']['logged']=true;
$GLOBALS['a1_test']['nonce']=true;
$_COOKIE[LOGGED_IN_COOKIE]='fake-dev-admin-cookie';
a1_assert(call_a1('/wc/v3/products/55',null,'valid-nonce-123')===null,
          'trusted cookie+nonce admin allowed');$checks++;
a1_assert(call_a1('/wc/v3/products/55',null,'bad-nonce') instanceof WP_Error,
          'bad nonce denied');$checks++;
unset($_COOKIE[LOGGED_IN_COOKIE]);
a1_assert(call_a1('/wc/v3/products/55',null,'valid-nonce-123') instanceof WP_Error,
          'API-key admin without real cookie denied');$checks++;
reset_a1();
$GLOBALS['a1_test']['origin']='https://www.beyourlover.com';
a1_assert(call_a1('/wc/v3/products/55')===null,'WWW untouched');$checks++;
reset_a1();
$GLOBALS['a1_test']['env']='production';
a1_assert(call_a1('/wc/v3/products/55')===null,'production environment untouched');$checks++;
reset_a1();
a1_assert(call_a1('/wc/v3/products',new stdClass()) instanceof stdClass,
          'prior security result respected');$checks++;
reset_a1();
a1_assert(call_a1('/wc/v3/products',null,'','other')===null,
          'unexpected request untouched');$checks++;
echo "PASS {$checks} Dev-only read-only product REST quarantine contracts\n";
