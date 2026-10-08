<?php
/** Standalone focused Cart V2 contract — no WordPress/Woo orders or database needed. */
define('ABSPATH', __DIR__);
$GLOBALS['hooks'] = [];
function add_action($hook, $callback, $priority=10) { $GLOBALS['hooks'][$hook] = $callback; }
class BYLTestProduct {
    public function __construct(private int $id, private array $upsells=[], private bool $visible=true) {}
    public function get_id() { return $this->id; }
    public function get_upsell_ids() { return $this->upsells; }
    public function is_type($type) { return $type === 'simple'; }
    public function is_visible() { return $this->visible; }
    public function is_purchasable() { return true; }
    public function is_in_stock() { return true; }
    public function get_status() { return 'publish'; }
}
class BYLTestCart {
    public array $items = [];
    public function get_cart() { return $this->items; }
    public function get_cross_sells() { return [101, 102]; }
}
$GLOBALS['products'] = [
    10 => new BYLTestProduct(10, [103, 104]),
    101 => new BYLTestProduct(101),
    102 => new BYLTestProduct(102, [], false),
    103 => new BYLTestProduct(103),
    104 => new BYLTestProduct(104),
    105 => new BYLTestProduct(105),
    106 => new BYLTestProduct(106),
    107 => new BYLTestProduct(107),
];
$GLOBALS['wc'] = (object)['cart'=>new BYLTestCart()];
$GLOBALS['wc']->cart->items = [['product_id'=>10, 'variation_id'=>0, 'data'=>$GLOBALS['products'][10]]];
function WC() { return $GLOBALS['wc']; }
function wc_get_product($id) { return $GLOBALS['products'][$id] ?? null; }
function get_the_terms($id, $taxonomy) { return [(object)['slug'=>'real-category']]; }
function wc_get_products($query) {
    if (isset($query['category'])) { return [$GLOBALS['products'][10], $GLOBALS['products'][105]]; }
    return [$GLOBALS['products'][106], $GLOBALS['products'][107]];
}
require dirname(__DIR__).'/theme/beyourlover-v3/inc/cart-experience.php';
function check($value,$text) { if (!$value) { fwrite(STDERR, "FAIL $text\n"); exit(1); } }
$products=byl_v3_cart_recommendations(4);
$ids=array_map(static fn($p)=>$p->get_id(),$products);
check($ids === [101,103,104,105], 'priority/crosssell-upsell-category');
check(!in_array(10,$ids,true),'exclude cart item');
check(!in_array(102,$ids,true),'exclude invisible item');
check(count($ids)===count(array_unique($ids)),'no duplicate');
check(($GLOBALS['hooks']['woocommerce_after_cart'] ?? null)==='byl_v3_render_cart_recommendations','Woo render hook');
$GLOBALS['wc']->cart->items = [];
check(byl_v3_cart_recommendations(4) === [], 'empty cart no recommendations');
$cart = file_get_contents(dirname(__DIR__).'/theme/beyourlover-v3/page-cart.php');
check(str_contains($cart, "do_shortcode('[woocommerce_cart]')"), 'native Woo cart authority');
check(str_contains($cart, "get_header('cart')") && str_contains($cart, "get_footer('cart')"),'focused shell');
$header = file_get_contents(dirname(__DIR__).'/theme/beyourlover-v3/header-cart.php');
check(!str_contains($header, 'byl-shell-nav'), 'no full global menu on cart');
$footer = file_get_contents(dirname(__DIR__).'/theme/beyourlover-v3/footer-cart.php');
check(!str_contains($footer, '© 2025'), 'dynamic copyright date');
echo "PASS cart-v2-contract\n";
