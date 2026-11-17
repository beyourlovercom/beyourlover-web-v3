<?php
/**
 * BYL staging-only A1 WooPayments Jetpack Sync product-meta quarantine.
 *
 * Prevent Dev product metadata events from being enqueued for remote
 * Jetpack synchronization and prevents the normal Sync shutdown sender.
 * Does not erase/replay historical queue data and does not disconnect
 * WooPayments payment credentials or modify gateway settings.
 *
 * This is not a proof against independent HTTP clients or operator-
 * invoked explicit remote-synchronization commands.
 */
defined('ABSPATH') || exit;

function byl_a1_dev_jetpack_sync_hold_site(): bool {
    return function_exists('wp_get_environment_type')
        && wp_get_environment_type() === 'staging'
        && function_exists('home_url')
        && untrailingslashit((string) home_url()) === 'https://dev.beyourlover.com';
}

add_filter('jetpack_sync_sender_should_load', static function ($allow) {
    return byl_a1_dev_jetpack_sync_hold_site() ? false : $allow;
}, PHP_INT_MAX, 1);

foreach ([
    'updated_post_meta',
    'added_post_meta',
    'deleted_post_meta',
    'save_post_product',
    'save_post_product_variation',
    'woocommerce_update_product',
    'woocommerce_update_product_variation',
] as $byl_a1_sync_hook) {
    add_filter('jetpack_sync_before_enqueue_' . $byl_a1_sync_hook,
        static function ($args) {
            return byl_a1_dev_jetpack_sync_hold_site() ? false : $args;
        }, PHP_INT_MAX, 1);
}
unset($byl_a1_sync_hook);
