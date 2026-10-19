<?php
/**
 * Ephemeral shipping UAT coupon. Dev staging only; WP-CLI eval-file, NEVER WWW.
 * Usage: wp --path=DEV eval-file path/to/file.php create
 *        wp --path=DEV eval-file path/to/file.php cleanup <coupon_id>
 */
declare(strict_types=1);
if (!defined('ABSPATH') || !class_exists('WC_Coupon')
    || wp_parse_url(home_url('/'), PHP_URL_HOST) !== 'dev.beyourlover.com'
    || wp_get_environment_type() !== 'staging') {
    throw new RuntimeException('Dev staging ONLY');
}
$mode = $args[0] ?? '';
$code = 'byldev-oversized-coupon-uat-20261019';
if ($mode === 'create') {
    $existing = wc_get_coupon_id_by_code($code);
    if ($existing) {
        throw new RuntimeException('Existing UAT coupon: refuse overwrite');
    }
    $coupon = new WC_Coupon();
    $coupon->set_code($code);
    $coupon->set_discount_type('fixed_product');
    $coupon->set_amount(30);
    $coupon->set_product_ids([25447]); // normal item: SKULL MAN, NOT PILLOW
    $coupon->set_individual_use(true);
    $coupon->set_usage_limit(1);
    $coupon->set_description('DEV UAT only; delete immediately after validation');
    $coupon->set_date_expires(time() + 3600);
    $id = $coupon->save();
    if (!$id || get_post_type($id) !== 'shop_coupon') {
        throw new RuntimeException('Dev UAT coupon not created');
    }
    update_post_meta($id, '_byl_dev_shipping_uat_only', '20261019');
    echo "DEV_UAT_COUPON_CREATED id={$id} code={$code}\n";
} elseif ($mode === 'cleanup') {
    $id = isset($args[1]) ? (int) $args[1] : 0;
    if (!$id || get_post_type($id) !== 'shop_coupon'
        || get_post_meta($id, '_byl_dev_shipping_uat_only', true) !== '20261019') {
        throw new RuntimeException('Coupon cleanup refused: provenance mismatch');
    }
    $coupon = new WC_Coupon($id);
    if ($coupon->get_code() !== $code) {
        throw new RuntimeException('Coupon cleanup refused: code mismatch');
    }
    $coupon->delete(true);
    if (get_post_type($id) === 'shop_coupon') {
        throw new RuntimeException('Coupon remained after delete');
    }
    echo "DEV_UAT_COUPON_REMOVED id={$id}\n";
} else {
    throw new InvalidArgumentException('Expected create or cleanup mode');
}
