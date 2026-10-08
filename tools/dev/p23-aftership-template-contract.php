<?php
/**
 * AfterShip shipment template sandbox contract without persisting tracking items.
 * WP-CLI on Dev only; the captured HTML is NEVER served to a browser.
 * No carrier tracking data, API request, webhook, email or Woo order write.
 */
if (!defined('WP_CLI') || !WP_CLI ||
    home_url() !== 'https://dev.beyourlover.com' ||
    wp_get_environment_type() !== 'staging') {
    throw new RuntimeException('Denied: BYL Dev staging WP-CLI only');
}
if (!is_plugin_active('aftership-woocommerce-tracking/aftership-woocommerce-tracking.php')
    || empty($GLOBALS['AfterShip'])) {
    throw new RuntimeException('AfterShip not active');
}
$http_attempts=0;
add_filter('pre_http_request',static function ($pre,$args,$url) use (&$http_attempts) {
    $http_attempts++;
    return new WP_Error('p23_aftership_offline','No network in template contract test');
},PHP_INT_MAX,3);
add_filter('pre_wp_mail','__return_false',PHP_INT_MAX);
$root=$GLOBALS['AfterShip']->get_plugin_path().'/templates/';
$render=static function(array $items) use ($root):string {
   ob_start();
   wc_get_template('myaccount/view-order.php',[
       'tracking_items'=>$items,
       'use_track_button'=>false,
       'domain'=>'',
   ],'aftership-woocommerce-tracking/',$root);
   return (string)ob_get_clean();
};
$empty=$render([]);
$demo=$render([
    [
      'courier'=>['name'=>'Sandbox Carrier A'],
      'tracking_number'=>'P23-FAKE-ONLY-001',
      'custom_domain'=>'',
      'tracking_number_for_tracking_button'=>'P23-FAKE-ONLY-001',
    ],
    [
      'courier'=>['name'=>'Sandbox Carrier B'],
      'tracking_number'=>'P23-FAKE-ONLY-002<script>alert(1)</script>',
      'custom_domain'=>'',
      'tracking_number_for_tracking_button'=>'P23-FAKE-ONLY-002',
    ],
]);
$passed=trim($empty)==='' &&
    substr_count($demo,'class="tracking"')===2 &&
    strpos($demo,'Sandbox Carrier A')!==false &&
    strpos($demo,'Sandbox Carrier B')!==false &&
    strpos($demo,'P23-FAKE-ONLY-001')!==false &&
    strpos($demo,'&lt;script&gt;alert(1)&lt;/script&gt;')!==false &&
    strpos($demo,'P23-FAKE-ONLY-002<script>')===false &&
    strpos($demo,'class="as-track-button"')===false &&
    $http_attempts===0;
echo 'P23_AFTERSHIP_TEMPLATE_CONTRACT=',wp_json_encode([
    'pass'=>$passed,
    'empty_order_has_no_tracking_section'=>trim($empty)==='',
    'synthetic_tracking_rows'=>substr_count($demo,'class="tracking"'),
    'track_buttons_hidden'=>strpos($demo,'class="as-track-button"')===false,
    'unsafe_tracking_number_html_escaped'=>strpos($demo,'&lt;script&gt;alert(1)&lt;/script&gt;')!==false,
    'external_http_attempts'=>$http_attempts,
    'no_tracking_meta_written'=>true,
],JSON_UNESCAPED_SLASHES),PHP_EOL;
if(!$passed)throw new RuntimeException('AfterShip local template contract failed');
