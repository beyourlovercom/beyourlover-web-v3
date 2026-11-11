<?php
/**
 * Plugin Name: BYL Woo Completion Guard
 * Description: Blocks unsupported WooCommerce completed transitions and bulk completion shortcuts.
 * Version: 0.1.0
 * Requires Plugins: woocommerce
 */
defined('ABSPATH') || exit;

final class BYL_Woo_Completion_Guard {
    public static function register(): void {
        add_filter('bulk_actions-edit-shop_order', [self::class, 'removeBulkComplete'], 99);
        add_filter('bulk_actions-woocommerce_page_wc-orders', [self::class, 'removeBulkComplete'], 99);
        add_filter('woocommerce_admin_order_actions', [self::class, 'removeQuickComplete'], 99, 2);
        add_action('admin_init', [self::class, 'blockForgedBulkComplete'], 1);
        add_action('woocommerce_before_order_object_save', [self::class, 'checkBeforeSave'], 1, 1);
        add_filter('rest_pre_dispatch', [self::class, 'rejectRestCompletionWithoutTracking'], 5, 3);
    }

    public static function removeBulkComplete(array $actions): array {
        unset($actions['mark_completed']);
        return $actions;
    }

    public static function removeQuickComplete(array $actions, $order): array {
        unset($actions['complete']);
        return $actions;
    }

    public static function blockForgedBulkComplete(): void {
        if (current_user_can('manage_options')) {
            return;
        }
        $page = isset($_REQUEST['page']) && is_string($_REQUEST['page'])
            ? sanitize_key(wp_unslash($_REQUEST['page'])) : '';
        $postType = isset($_REQUEST['post_type']) && is_string($_REQUEST['post_type'])
            ? sanitize_key(wp_unslash($_REQUEST['post_type'])) : '';
        if ($page !== 'wc-orders' && $postType !== 'shop_order') {
            return;
        }
        foreach (['action', 'action2'] as $key) {
            $value = isset($_REQUEST[$key]) && is_string($_REQUEST[$key])
                ? sanitize_key(wp_unslash($_REQUEST[$key])) : '';
            if ($value === 'mark_completed') {
                self::log(0, 'blocked_bulk', 'bulk completion forbidden for non-admin');
                wp_die('请通过 ERP 发货流程完成订单；不允许批量设置 Completed。', 'BYL Completion Guard', ['response' => 403]);
            }
        }
    }

    /**
     * Preflight Woo v3 REST status changes before the controller mutates the
     * order. REST pre-insert hooks can run after partial updates in Woo core.
     */
    public static function rejectRestCompletionWithoutTracking($result, $server, $request) {
        if ($result !== null || !is_object($request) ||
            !in_array($request->get_method(), ['POST', 'PUT', 'PATCH'], true)) {
            return $result;
        }
        if (!preg_match('~^/wc/v3/orders/([1-9][0-9]*)$~', $request->get_route(), $m)) {
            return $result;
        }
        if (!in_array((string) $request->get_param('status'), ['completed', 'completed-delivered'], true)) {
            return $result;
        }
        if (!function_exists('wc_get_order')) {
            return $result;
        }
        $order = wc_get_order((int) $m[1]);
        if (!$order || in_array($order->get_status(), ['completed', 'completed-delivered'], true) ||
            !self::requiresShipping($order) || self::hasTracking($order)) {
            return $result;
        }
        self::log((int) $m[1], 'blocked_rest_no_tracking', 'REST preflight: no verified package');
        return new WP_Error('byl_no_tracking',
            '订单缺少承运商与真实 Tracking，禁止通过 API 设置 Completed。',
            ['status' => 409]);
    }

    public static function checkBeforeSave($order): void {
        if (!class_exists('WC_Order') || !($order instanceof WC_Order) ||
            $order->get_type() !== 'shop_order' || !$order->get_id()) {
            return;
        }

        $changes = $order->get_changes();
        $target = isset($changes['status']) ? (string) $changes['status'] : '';
        if (!in_array($target, ['completed', 'completed-delivered'], true)) {
            return;
        }

        // An existing order being saved without a NEW status transition is never blocked.
        // Exempt orders which have only virtual items; they need no shipment.
        if (!self::requiresShipping($order)) {
            return;
        }

        if (!self::hasTracking($order)) {
            self::log((int) $order->get_id(), 'blocked_no_tracking', 'no verifiable AfterShip package');
            throw new WC_Data_Exception(
                'byl_no_tracking',
                '该订单没有真实承运商与 Tracking，禁止设置为 Completed。'
            );
        }

        // WooCommerce API writes, including the ERP's dedicated integration, must
        // still pass the tracking gate above. Ordinary WP admin UI may not finish.
        if (is_admin() && !(defined('REST_REQUEST') && REST_REQUEST) &&
            !current_user_can('manage_options')) {
            self::log((int) $order->get_id(), 'blocked_ui', 'non-admin direct completion');
            throw new WC_Data_Exception(
                'byl_no_direct_complete',
                '请通过 ERP 发货同步流程完成订单，普通运营账户不能直接完结。'
            );
        }
    }

    private static function requiresShipping($order): bool {
        $seen = false;
        foreach ($order->get_items('line_item') as $item) {
            $seen = true;
            $product = $item->get_product();
            if (!$product || $product->needs_shipping()) {
                return true;
            }
        }
        // Unknown or empty line-item sets should never authorize an arbitrary
        // externally triggered completion.
        return !$seen;
    }

    private static function hasTracking($order): bool {
        $items = $order->get_meta('_aftership_tracking_items', true);
        if (is_array($items)) {
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $number = trim((string) ($item['tracking_number'] ?? ''));
                $carrier = trim((string) ($item['slug'] ?? $item['provider'] ?? ''));
                if (self::plausibleTracking($number) && $carrier !== '') {
                    return true;
                }
            }
        }
        $number = trim((string) $order->get_meta('_aftership_tracking_number', true));
        $carrier = trim((string) $order->get_meta('_aftership_tracking_provider_name', true));
        return self::plausibleTracking($number) && $carrier !== '';
    }

    private static function plausibleTracking(string $number): bool {
        return strlen($number) >= 6 &&
            !in_array(strtolower($number), ['pending', 'unknown', 'no tracking', '000000'], true);
    }

    private static function log(int $id, string $reason, string $detail): void {
        if (!function_exists('wc_get_logger')) {
            return;
        }
        wc_get_logger()->warning(
            "order_id={$id}; reason={$reason}; user_id=".get_current_user_id().
            "; source=".(defined('REST_REQUEST') && REST_REQUEST ? 'rest' : (wp_doing_ajax() ? 'ajax' : 'admin')).
            "; detail={$detail}",
            ['source' => 'byl-woo-completion-guard']
        );
    }
}
add_action('plugins_loaded', [BYL_Woo_Completion_Guard::class, 'register'], 20);
