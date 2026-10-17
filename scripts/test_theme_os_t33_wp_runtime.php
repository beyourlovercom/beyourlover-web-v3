<?php
/**
 * OPT-IN ONLY: Real WP 7.1.3 / Woo 11.2.1 synthetic CLI runtime compatibility.
 * Requires WP-CLI eval-file, a fresh local DB on a private socket, no Dev/WWW.
 * NOT in Foundation CI: installer and real database are deliberately separate.
 */
$_SERVER['HTTP_HOST'] = 'byl-t3.invalid';
$base = getenv('BYL_T3_SANDBOX_ROOT');
if (!$base || !str_starts_with((string) realpath(ABSPATH), (string) realpath($base) . '/src/wordpress')
    || DB_NAME !== 'byl_t3_synthetic' || !str_contains(DB_HOST, $base . '/private.sock')
    || get_option('siteurl') !== 'http://byl-t3.invalid'
    || !defined('WP_HTTP_BLOCK_EXTERNAL') || !WP_HTTP_BLOCK_EXTERNAL
    || !defined('WP_ENVIRONMENT_TYPE') || WP_ENVIRONMENT_TYPE !== 'local') {
    fwrite(STDERR, "T33_ISOLATION_FAIL_CLOSED\n");
    exit(90);
}
add_filter('pre_wp_mail', '__return_false');
wp_set_current_user(0);
$created = [];
$GLOBALS['t33_checks'] = 0;
$failed = false;
function t33_assert($condition, string $label): void {
    if (!$condition) { throw new RuntimeException("FAIL $label"); }
$GLOBALS['t33_checks']++;
    echo "PASS $label\n";
}
try {
    t33_assert(get_bloginfo('version') === '7.1.3' && WC()->version === '11.2.1',
               'EXACT_WP_WOO_VERSIONS');
    t33_assert(class_exists('WC_Cart') && class_exists('WC_Product_Variation'),
               'REAL_WOO_CLASSES');
    t33_assert(count(wc_get_orders(['limit'=>1, 'return'=>'ids'])) === 0,
               'ZERO_SYNTHETIC_ORDERS_BEFORE');
    $simple = new WC_Product_Simple();
    $simple->set_name('T33-SYNTHETIC-STOCKED');
    $simple->set_status('publish');
    $simple->set_regular_price('19.99');
    $simple->set_manage_stock(true);
    $simple->set_stock_quantity(4);
    $simple->set_stock_status('instock');
    $simple_id = $simple->save();
    $created[] = $simple_id;
    $out = new WC_Product_Simple();
    $out->set_name('T33-SYNTHETIC-NO-STOCK');
    $out->set_status('publish');
    $out->set_regular_price('9.99');
    $out->set_manage_stock(true);
    $out->set_stock_quantity(0);
    $out->set_stock_status('outofstock');
    $out_id = $out->save();
    $created[] = $out_id;
    t33_assert(wc_get_product($simple_id)->is_in_stock()
               && !wc_get_product($out_id)->is_in_stock(), 'WOO_STOCK_STATES');
    $var = new WC_Product_Variable();
    $var->set_name('T33-SYNTHETIC-VARIABLE');
    $var->set_status('publish');
    $attribute = new WC_Product_Attribute();
    $attribute->set_name('Color');
    $attribute->set_options(['Black','Wine']);
    $attribute->set_visible(true);
    $attribute->set_variation(true);
    $var->set_attributes([$attribute]);
    $var_id = $var->save();
    $created[] = $var_id;
    $v = new WC_Product_Variation();
    $v->set_parent_id($var_id);
    $v->set_attributes(['color'=>'Black']);
    $v->set_status('publish');
    $v->set_regular_price('24.99');
    $v->set_manage_stock(true);
    $v->set_stock_quantity(2);
    $v->set_stock_status('instock');
    $v_id = $v->save();
    $created[] = $v_id;
    WC_Product_Variable::sync($var_id);
    t33_assert(wc_get_product($v_id)->is_in_stock()
               && in_array($v_id, wc_get_product($var_id)->get_children(), true),
               'WOO_VARIABLE_CHILD_AND_STOCK');
    $product_response = rest_do_request(new WP_REST_Request('GET','/wc/store/v1/products/'.$var_id));
    $product_data = $product_response->get_data();
    t33_assert($product_response->get_status()===200
               && ($product_data['type']??'')==='variable' && isset($product_data['prices']),
               'WOO_STORE_API_VARIABLE_READ');
    wc_load_cart();
    WC()->cart->empty_cart(true);
    t33_assert((bool) WC()->cart->add_to_cart($simple_id,1),
               'CART_STOCKED_ITEM_ALLOWED');
    t33_assert(WC()->cart->add_to_cart($out_id,1) === false,
               'CART_OUT_OF_STOCK_DENIED');
    t33_assert(WC()->cart->add_to_cart($simple_id,5) === false,
               'CART_EXCESS_QUANTITY_DENIED');
    t33_assert((bool) WC()->cart->add_to_cart($var_id,1,$v_id,['attribute_color'=>'Black']),
               'CART_SELECTED_VARIATION_ALLOWED');
    WC()->cart->calculate_totals();
    t33_assert(WC()->cart->get_cart_contents_count()===2
               && (float)(WC()->cart->get_totals()['total']??0)>0, 'NATIVE_WOO_TOTALS');
    $nonce=wp_create_nonce('wc_store_api');
    t33_assert((bool)wp_verify_nonce($nonce,'wc_store_api')
               && wp_verify_nonce($nonce,'wrong-action')===false
               && wp_verify_nonce('tampered-value','wc_store_api')===false, 'WORDPRESS_NONCE_ACCEPT_REJECT');
    $cart_response=rest_do_request(new WP_REST_Request('GET','/wc/store/v1/cart'));
    $cart_data=$cart_response->get_data();
    t33_assert($cart_response->get_status()===200
               && isset($cart_data['items'],$cart_data['totals']), 'WOO_STORE_API_CART_GET');
    // Exercise real Woo Store API nonce handling via internal REST dispatch.
    // Never send to Dev/WWW or a payment provider.
    $invalid_cart = new WP_REST_Request('POST','/wc/store/v1/cart/add-item');
    $invalid_cart->set_header('Nonce','invalid-t33-token');
    $invalid_cart->set_param('id',$simple_id);
    $invalid_cart->set_param('quantity',1);
    $invalid_cart_response=rest_do_request($invalid_cart);
    $invalid_cart_data=$invalid_cart_response->get_data();
    t33_assert($invalid_cart_response->get_status()===403
               && ($invalid_cart_data['code']??'')==='woocommerce_rest_invalid_nonce'
               && WC()->cart->get_cart_contents_count()===2, 'CART_POST_INVALID_NONCE_DENIED');
    $invalid_checkout = new WP_REST_Request('POST','/wc/store/v1/checkout');
    $invalid_checkout->set_header('Nonce','invalid-t33-token');
    $invalid_checkout->set_param('billing_address',[
        'first_name'=>'Synthetic', 'last_name'=>'User',
        'address_1'=>'123 Demo St', 'city'=>'Test City', 'state'=>'CA',
        'postcode'=>'90001', 'country'=>'US', 'email'=>'sandbox@example.invalid',
        'phone'=>'5550001234'
    ]);
    $invalid_checkout_response=rest_do_request($invalid_checkout);
    $invalid_checkout_data=$invalid_checkout_response->get_data();
    t33_assert($invalid_checkout_response->get_status()===403
               && ($invalid_checkout_data['code']??'')==='woocommerce_rest_invalid_nonce'
               && count(wc_get_orders(['limit'=>1,'return'=>'ids']))===0,
               'CHECKOUT_POST_INVALID_NONCE_DENIED_WITHOUT_ORDER');
    WC()->cart->empty_cart(true);
    t33_assert((bool) WC()->cart->add_to_cart($simple_id,1), 'STOCK_CHANGE_FIXTURE_IN_CART');
    $p=wc_get_product($simple_id);
    $p->set_stock_quantity(0);
    $p->set_stock_status('outofstock');
    $p->save();
    foreach(WC()->cart->get_cart() as $key=>$item) {
        WC()->cart->cart_contents[$key]['data']=wc_get_product($item['product_id']);
    }
    wc_clear_notices();
    t33_assert(WC()->cart->check_cart_items()===false
               && count(wc_get_notices('error'))>0, 'REFRESHED_CART_STOCK_CHANGE_DETECTED');
    $p=wc_get_product($simple_id);
    $p->set_stock_quantity(4);
    $p->set_stock_status('instock');
    $p->save();
    WC()->cart->empty_cart(true);
    wc_clear_notices();
    $fields=WC()->checkout()->get_checkout_fields();
    t33_assert(isset($fields['billing']['billing_email'], $fields['billing']['billing_first_name'])
               && WC_Validation::is_email('sandbox@example.invalid')
               && !WC_Validation::is_email('invalid-email'), 'CHECKOUT_FIELDS_VALIDATION_ONLY');
    t33_assert(count(wc_get_orders(['limit'=>1,'return'=>'ids']))===0,
               'ZERO_SYNTHETIC_ORDERS_AFTER');
} catch(Throwable $error) {
    $failed = true;
    fwrite(STDERR, $error->getMessage()."\n");
} finally {
    if (WC()->cart instanceof WC_Cart) { WC()->cart->empty_cart(true); }
    foreach (array_reverse($created) as $id) {
        $product=wc_get_product($id);
        if ($product) { $product->delete(true); }
    }
}
echo "T33_RUNTIME_PASSED_CHECKS=".$GLOBALS["t33_checks"]."\n";
exit($failed ? 1 : 0);
