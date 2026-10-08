<?php
/**
 * First-party checkout and shipping promotion policy.
 */
declare(strict_types=1);

if (!defined('ABSPATH')) { exit; }

final class BYL_Shipping_Promotion_Policy {
    public const OPTION_NAME = 'byl_shipping_promotion_settings';
    public const SETTINGS_GROUP = 'byl_platform_checkout_shipping';
    public const SETTINGS_PAGE = 'byl-platform-checkout-shipping';
    public const SCHEMA_VERSION = 1;
    public const ELIGIBILITY_BASIS = 'pre_discount_merchandise_subtotal';

    public static function boot(): void {
        add_action('admin_menu', [self::class, 'register_admin_menu']);
        add_action('admin_init', [self::class, 'register_settings']);
    }

    public static function defaults(bool $infer_woo_thresholds = true): array {
        $settings = [
            'schema_version' => self::SCHEMA_VERSION,
            'mode' => 'automatic',
            'free_shipping_code' => 'BYL49',
            'global_threshold' => 49.00,
            'eligibility_basis' => self::ELIGIBILITY_BASIS,
            'zone_overrides' => [],
        ];

        foreach (self::zone_catalog() as $zone_id => $zone) {
            $woo_threshold = $zone['woo_min_amount'];
            $settings['zone_overrides'][(string) $zone_id] = [
                'mode' => $infer_woo_thresholds && $woo_threshold !== null ? 'override' : 'global',
                'threshold' => $woo_threshold !== null ? $woo_threshold : $settings['global_threshold'],
            ];
        }

        return $settings;
    }

    public static function get_settings(): array {
        $saved = get_option(self::OPTION_NAME, null);
        $has_saved_settings = is_array($saved);
        // Always infer Woo thresholds first. Saved settings overlay this base so newly
        // added zones retain their existing Woo free-shipping minimum.
        $settings = self::defaults(true);

        if ($has_saved_settings) {
            $settings['schema_version'] = self::SCHEMA_VERSION;
            $settings['mode'] = self::valid_mode($saved['mode'] ?? $settings['mode']);
            $settings['free_shipping_code'] = self::clean_code($saved['free_shipping_code'] ?? $settings['free_shipping_code']);
            $settings['global_threshold'] = self::clean_threshold($saved['global_threshold'] ?? $settings['global_threshold'], $settings['global_threshold']);
            $settings['eligibility_basis'] = self::ELIGIBILITY_BASIS;

            if (isset($saved['zone_overrides']) && is_array($saved['zone_overrides'])) {
                foreach ($saved['zone_overrides'] as $zone_id => $config) {
                    if (!isset($settings['zone_overrides'][(string) absint($zone_id)]) || !is_array($config)) { continue; }
                    $settings['zone_overrides'][(string) absint($zone_id)] = [
                        'mode' => ($config['mode'] ?? '') === 'override' ? 'override' : 'global',
                        'threshold' => self::clean_threshold($config['threshold'] ?? $settings['zone_overrides'][(string) absint($zone_id)]['threshold'], $settings['zone_overrides'][(string) absint($zone_id)]['threshold']),
                    ];
                }
            }
        }

        return $settings;
    }

    public static function get_mode(): string { return self::get_settings()['mode']; }
    public static function get_code(): string { return self::get_settings()['free_shipping_code']; }
    public static function get_global_threshold(): float { return self::get_settings()['global_threshold']; }
    public static function get_eligibility_basis(): string { return self::ELIGIBILITY_BASIS; }

    /**
     * Runtime activation is deliberately stricter than the admin display model.
     * Derived WooCommerce defaults are safe to display, but never activate policy.
     */
    public static function has_persisted_settings(): bool {
        $saved = get_option(self::OPTION_NAME, null);
        if (!is_array($saved) || (int) ($saved['schema_version'] ?? 0) !== self::SCHEMA_VERSION) { return false; }
        if (!in_array($saved['mode'] ?? null, ['automatic', 'require_code'], true)) { return false; }
        if (!self::is_valid_code($saved['free_shipping_code'] ?? null)) { return false; }
        if (!self::is_valid_number($saved['global_threshold'] ?? null)) { return false; }
        if ((float) $saved['global_threshold'] < 0) { return false; }
        if (($saved['eligibility_basis'] ?? null) !== self::ELIGIBILITY_BASIS) { return false; }
        if (!isset($saved['zone_overrides']) || !is_array($saved['zone_overrides'])) { return false; }
        foreach ($saved['zone_overrides'] as $zone_id => $config) {
            if (!is_string($zone_id) && !is_int($zone_id)) { return false; }
            if (!preg_match('/^\d+$/', (string) $zone_id) || !is_array($config)) { return false; }
            if (!in_array($config['mode'] ?? null, ['global', 'override'], true)) { return false; }
            if (!self::is_valid_number($config['threshold'] ?? null) || (float) $config['threshold'] < 0) { return false; }
        }
        return true;
    }

    public static function is_runtime_active(): bool { return self::has_persisted_settings(); }

    public static function get_persisted_settings(): ?array {
        if (!self::has_persisted_settings()) { return null; }
        $settings = get_option(self::OPTION_NAME, null);
        $settings['free_shipping_code'] = self::normalize_code($settings['free_shipping_code']);
        return $settings;
    }

    public static function normalize_code(string $code): string {
        return function_exists('wc_format_coupon_code') ? wc_format_coupon_code($code) : strtoupper($code);
    }

    private static function is_valid_code($code): bool {
        if (!is_string($code) || $code === '') { return false; }
        return preg_match('/^[A-Z0-9_-]{1,50}$/', $code) === 1;
    }

    private static function is_valid_number($value): bool {
        return !is_bool($value) && is_numeric($value) && is_finite((float) $value) && (float) $value <= 999999.99;
    }

    public static function get_zone_config(int $zone_id): array {
        $settings = self::get_settings();
        $key = (string) absint($zone_id);
        $config = $settings['zone_overrides'][$key] ?? [
            'mode' => 'global',
            'threshold' => $settings['global_threshold'],
        ];
        $catalog = self::zone_catalog();
        return [
            'zone_id' => absint($zone_id),
            'name' => $catalog[$key]['name'] ?? ($zone_id === 0 ? 'Rest of world' : 'Zone ' . absint($zone_id)),
            'mode' => $config['mode'],
            'threshold' => (float) $config['threshold'],
            'effective_threshold' => $config['mode'] === 'override' ? (float) $config['threshold'] : $settings['global_threshold'],
            'eligibility_basis' => self::ELIGIBILITY_BASIS,
        ];
    }

    public static function get_zone_effective_threshold(int $zone_id): float {
        return self::get_zone_config($zone_id)['effective_threshold'];
    }

    public static function get_persisted_zone_effective_threshold(int $zone_id): ?float {
        $settings = self::get_persisted_settings();
        if ($settings === null) { return null; }
        $config = $settings['zone_overrides'][(string) absint($zone_id)] ?? null;
        return is_array($config) && ($config['mode'] ?? '') === 'override'
            ? (float) $config['threshold']
            : (float) $settings['global_threshold'];
    }

    public static function sanitize_settings($input): array {
        $input = is_array($input) ? $input : [];
        $current = self::get_settings();
        $clean = [
            'schema_version' => self::SCHEMA_VERSION,
            'mode' => self::valid_mode($input['mode'] ?? $current['mode']),
            'free_shipping_code' => self::clean_code($input['free_shipping_code'] ?? $current['free_shipping_code']),
            'global_threshold' => self::clean_threshold($input['global_threshold'] ?? $current['global_threshold'], $current['global_threshold']),
            'eligibility_basis' => self::ELIGIBILITY_BASIS,
            'zone_overrides' => [],
        ];

        $posted_zones = isset($input['zone_overrides']) && is_array($input['zone_overrides']) ? $input['zone_overrides'] : [];
        foreach (self::zone_catalog() as $zone_id => $zone) {
            $key = (string) $zone_id;
            $posted = isset($posted_zones[$key]) && is_array($posted_zones[$key]) ? $posted_zones[$key] : ($current['zone_overrides'][$key] ?? []);
            $fallback_threshold = $current['zone_overrides'][$key]['threshold'] ?? ($zone['woo_min_amount'] ?? $clean['global_threshold']);
            $clean['zone_overrides'][$key] = [
                'mode' => ($posted['mode'] ?? '') === 'override' ? 'override' : 'global',
                'threshold' => self::clean_threshold($posted['threshold'] ?? $fallback_threshold, $fallback_threshold),
            ];
        }
        return $clean;
    }

    public static function register_admin_menu(): void {
        add_menu_page('BYL Platform', 'BYL Platform', 'manage_woocommerce', 'byl-platform', [self::class, 'render_platform_page'], 'dashicons-admin-generic');
        add_submenu_page('byl-platform', 'Checkout / Shipping', 'Checkout / Shipping', 'manage_woocommerce', self::SETTINGS_PAGE, [self::class, 'render_settings_page']);
    }

    public static function register_settings(): void {
        register_setting(self::SETTINGS_GROUP, self::OPTION_NAME, [
            'type' => 'array',
            'sanitize_callback' => [self::class, 'sanitize_settings'],
        ]);
        add_filter('option_page_capability_' . self::SETTINGS_GROUP, [self::class, 'option_page_capability']);
    }

    public static function option_page_capability(string $capability): string {
        return 'manage_woocommerce';
    }

    public static function render_platform_page(): void {
        if (!current_user_can('manage_woocommerce')) { wp_die(esc_html__('You do not have permission to access this page.', 'byl-platform')); }
        echo '<div class="wrap"><h1>BYL Platform</h1><p><a href="' . esc_url(admin_url('admin.php?page=' . self::SETTINGS_PAGE)) . '">Checkout / Shipping</a></p></div>';
    }

    public static function render_settings_page(): void {
        if (!current_user_can('manage_woocommerce')) { wp_die(esc_html__('You do not have permission to access this page.', 'byl-platform')); }
        $settings = self::get_settings();
        echo '<div class="wrap"><h1>Checkout / Shipping</h1><form method="post" action="options.php">';
        settings_fields(self::SETTINGS_GROUP);
        echo '<h2>Free Shipping Promotion</h2>';
        echo '<p>Policy settings only. Existing WooCommerce free-shipping method settings are never written by this page.</p>';
        echo '<table class="form-table" role="presentation">';
        echo '<tr><th scope="row"><label for="byl-mode">Mode</label></th><td><select id="byl-mode" name="' . esc_attr(self::OPTION_NAME) . '[mode]">';
        echo '<option value="automatic"' . selected($settings['mode'], 'automatic', false) . '>Automatic free shipping (recommended)</option>';
        echo '<option value="require_code"' . selected($settings['mode'], 'require_code', false) . '>Require code for free shipping</option></select></td></tr>';
        echo '<tr><th scope="row"><label for="byl-code">Free-shipping code</label></th><td><input id="byl-code" class="regular-text" name="' . esc_attr(self::OPTION_NAME) . '[free_shipping_code]" value="' . esc_attr($settings['free_shipping_code']) . '"' . disabled($settings['mode'], 'automatic', false) . ' /><p class="description">Used only when require-code mode is selected.</p></td></tr>';
        echo '<tr><th scope="row"><label for="byl-global-threshold">Global threshold</label></th><td><input id="byl-global-threshold" type="number" min="0" step="0.01" name="' . esc_attr(self::OPTION_NAME) . '[global_threshold]" value="' . esc_attr(number_format($settings['global_threshold'], 2, '.', '')) . '" /></td></tr>';
        echo '<tr><th scope="row">Eligibility basis</th><td><code>' . esc_html(self::ELIGIBILITY_BASIS) . '</code><p class="description">Fixed contract value; this is not editable in S1/S2.</p></td></tr>';
        echo '</table>';
        $warning_hidden = $settings['mode'] === 'require_code' ? ' style="display:block !important" aria-hidden="false"' : ' hidden style="display:none !important" aria-hidden="true"';
        echo '<style id="byl-shipping-promotion-admin-styles">#byl-code-warning.byl-code-warning-callout{background:#fff8e5;border:1px solid #dba617;border-left-width:4px;margin:12px 0;padding:1px 12px;}#byl-code-warning.byl-code-warning-callout p{margin:10px 0;}</style>';
        echo '<div id="byl-code-warning" class="byl-code-warning-callout" role="alert"' . $warning_hidden . '><p><strong>Important:</strong> enabling require-code mode means <code>' . esc_html($settings['free_shipping_code']) . '</code> occupies the order\'s only promo-code slot; affiliate or other promo codes cannot be used simultaneously.</p></div>';
        echo '<h2>Zone thresholds</h2><p>Choose the global threshold or preserve a zone-specific WooCommerce threshold. Rest of world is included as zone 0.</p>';
        echo '<table class="widefat striped"><thead><tr><th>Zone</th><th>Setting</th><th>Threshold</th><th>Effective threshold</th></tr></thead><tbody>';
        foreach (self::zone_catalog() as $zone_id => $zone) {
            $config = $settings['zone_overrides'][(string) $zone_id] ?? ['mode' => 'global', 'threshold' => $settings['global_threshold']];
            $prefix = esc_attr(self::OPTION_NAME . '[zone_overrides][' . $zone_id . ']');
            echo '<tr><td>' . esc_html($zone['name']) . ' <code>' . esc_html((string) $zone_id) . '</code></td><td><select name="' . $prefix . '[mode]">';
            echo '<option value="global"' . selected($config['mode'], 'global', false) . '>Use global threshold</option><option value="override"' . selected($config['mode'], 'override', false) . '>Override</option></select></td>';
            echo '<td><input type="number" min="0" step="0.01" name="' . $prefix . '[threshold]" value="' . esc_attr(number_format((float) $config['threshold'], 2, '.', '')) . '" /></td><td>' . esc_html(number_format($config['mode'] === 'override' ? (float) $config['threshold'] : (float) $settings['global_threshold'], 2)) . '</td></tr>';
        }
        echo '</tbody></table>';
        submit_button();
        echo '</form><script>(function(){var mode=document.getElementById("byl-mode"),code=document.getElementById("byl-code"),warning=document.getElementById("byl-code-warning");if(!mode||!code){return;}var sync=function(){var requireCode=mode.value==="require_code";code.disabled=!requireCode;if(warning){warning.hidden=!requireCode;warning.style.setProperty("display",requireCode?"block":"none","important");warning.setAttribute("aria-hidden",requireCode?"false":"true");}};mode.addEventListener("change",sync);sync();}());</script></div>';
    }

    private static function valid_mode($mode): string { return $mode === 'require_code' ? 'require_code' : 'automatic'; }

    private static function clean_code($code): string {
        $code = strtoupper(sanitize_text_field((string) $code));
        return preg_match('/^[A-Z0-9_-]{1,50}$/', $code) ? $code : 'BYL49';
    }

    private static function clean_threshold($threshold, float $fallback = 49.00): float {
        if (!is_numeric($threshold) || !is_finite((float) $threshold)) { return $fallback; }
        $threshold = (float) $threshold;
        return round(max(0.0, min(999999.99, $threshold)), 2);
    }

    private static function zone_catalog(): array {
        $catalog = [];
        if (class_exists('WC_Shipping_Zones') && method_exists('WC_Shipping_Zones', 'get_zones')) {
            foreach ((array) WC_Shipping_Zones::get_zones() as $zone) {
                if (!is_array($zone) || !isset($zone['zone_id'])) { continue; }
                $zone_id = absint($zone['zone_id']);
                $catalog[(string) $zone_id] = ['name' => (string) ($zone['zone_name'] ?? 'Zone ' . $zone_id), 'woo_min_amount' => self::free_shipping_min_amount($zone['shipping_methods'] ?? [])];
            }
        }
        $rest = ['name' => 'Rest of world', 'woo_min_amount' => null];
        if (class_exists('WC_Shipping_Zones') && method_exists('WC_Shipping_Zones', 'get_zone')) {
            $zone = WC_Shipping_Zones::get_zone(0);
            if (is_object($zone) && method_exists($zone, 'get_shipping_methods')) { $rest['woo_min_amount'] = self::free_shipping_min_amount($zone->get_shipping_methods()); }
        }
        $catalog['0'] = $rest;
        ksort($catalog, SORT_NUMERIC);
        return $catalog;
    }

    private static function free_shipping_min_amount($methods): ?float {
        foreach ((array) $methods as $method) {
            if (!is_object($method)) { continue; }
            $method_id = method_exists($method, 'get_method_id')
                ? $method->get_method_id()
                : (isset($method->id) ? $method->id : ($method->method_id ?? ''));
            if ($method_id !== 'free_shipping') { continue; }
            $instance_id = method_exists($method, 'get_instance_id') ? $method->get_instance_id() : absint($method->instance_id ?? 0);
            $settings = method_exists($method, 'get_instance_option') ? ['min_amount' => $method->get_instance_option('min_amount', '')] : [];
            if ($settings['min_amount'] === '' && function_exists('get_option')) { $settings = get_option('woocommerce_free_shipping_' . $instance_id . '_settings', []); }
            if (is_array($settings) && isset($settings['min_amount']) && is_numeric($settings['min_amount'])) { return self::clean_threshold($settings['min_amount']); }
        }
        return null;
    }
}
