<?php
/**
 * BYL BF2026 Promotion UI V1 — presentation-only extension.
 * Integrate into existing black-friday-2026 plugin; no price/cart/order writes.
 */
if (!defined('ABSPATH')) { exit; }

final class BYL_BF2026_Promotion_UI {
    const VERSION = '1.1.0-dev-rc2';
    const OPTION = 'byl_bf2026_promotion_ui_v1';
    const SETTINGS_SLUG = 'byl-bf2026-promotion-ui';
    private static $booted = false;

    public static function boot(): void {
        if (self::$booted) { return; }
        self::$booted = true;
        add_action('rest_api_init', [__CLASS__, 'register_status_route']);
        add_action('admin_menu', [__CLASS__, 'register_settings'], 35);
        add_action('admin_post_byl_bf2026_promotion_ui_save', [__CLASS__, 'save_settings']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'assets'], 50);
        add_action('wp_body_open', [__CLASS__, 'announcement'], 2);
        add_filter('woocommerce_get_price_html', [__CLASS__, 'price_html'], 99, 2);
        add_filter('woocommerce_cart_item_price', [__CLASS__, 'cart_item_price'], 99, 3);
        add_filter('woocommerce_widget_cart_item_quantity', [__CLASS__, 'mini_cart_quantity'], 99, 3);
    }

    public static function settings(): array {
        $defaults = [
            'enabled'=>'no', 'start'=>'2026-11-10 00:00', 'end'=>'2026-12-01 00:00',
            'announcement'=>'yes','product'=>'yes','cart'=>'yes','drawer'=>'yes','sticky'=>'yes',
            'headline'=>'Black Friday Sale',
        ];
        $stored = function_exists('get_option') ? get_option(self::OPTION, []) : [];
        return array_merge($defaults, is_array($stored) ? $stored : []);
    }

    public static function epoch(string $date): int {
        try {
            $tz = new DateTimeZone('America/Los_Angeles');
            $dt = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $date, $tz);
            return $dt && $dt->format('Y-m-d H:i') === $date ? (int)$dt->getTimestamp() : 0;
        } catch (Throwable $e) { return 0; }
    }

    public static function discount_percent($regular, $effective): int {
        if (!is_numeric($regular) || !is_numeric($effective)) { return 0; }
        $r = (float)$regular; $p = (float)$effective;
        if (!is_finite($r) || !is_finite($p) || $r <= 0 || $p <= 0 || $p >= $r) { return 0; }
        return max(0, min(99, (int)floor((($r-$p)/$r)*100+0.0000001)));
    }

    public static function state(?int $now = null): array {
        $now = $now ?? time(); $s = self::settings();
        $start = self::epoch((string)$s['start']); $end = self::epoch((string)$s['end']);
        $host = function_exists('home_url') ? (string)wp_parse_url(home_url('/'), PHP_URL_HOST) : '';
        $active = in_array($host,['dev.beyourlover.com','www.beyourlover.com'],true)
            && $s['enabled']==='yes' && $start>0 && $end>$start && $now>=$start && $now<$end;
        return ['active'=>$active,'start'=>$start,'end'=>$end,'now'=>$now];
    }
    private static function enabled(string $slot = ''): bool {
        if (!class_exists('WooCommerce') || !self::state()['active']) { return false; }
        $s=self::settings();
        return $slot==='' || (isset($s[$slot]) && $s[$slot]==='yes');
    }
    public static function register_status_route(): void {
        register_rest_route('byl-bf2026/v1','/promotion-status',[
            'methods'=>'GET','permission_callback'=>'__return_true',
            'callback'=>static function(){
                $r = new WP_REST_Response(self::state(),200);
                $r->header('Cache-Control','no-store, private, max-age=0');
                return $r;
            }
        ]);
    }
    public static function assets(): void {
        if (is_admin() || !self::enabled()) { return; }
        $dir = dirname(__DIR__);
        $base = plugin_dir_url($dir.'/black-friday-2026.php');
        wp_enqueue_style('byl-bf2026-promotion-ui',$base.'assets/promotion-ui.css',[],self::VERSION);
        wp_enqueue_script('byl-bf2026-promotion-ui',$base.'assets/promotion-ui.js',[],self::VERSION,true);
        $settings = self::settings();
        $data = [
            'statusUrl'=>esc_url_raw(rest_url('byl-bf2026/v1/promotion-status')),
            'storeCartUrl'=>esc_url_raw(rest_url('wc/store/v1/cart')),
            'flags'=>array_intersect_key($settings,array_flip(['announcement','product','cart','drawer','sticky'])),
        ];
        wp_add_inline_script('byl-bf2026-promotion-ui','window.BYLBf2026Promotion='.wp_json_encode($data).';','before');
    }
    public static function announcement(): void {
        if (!self::enabled('announcement')) { return; }
        $headline = sanitize_text_field((string)self::settings()['headline']);
        if ($headline==='') { $headline='Black Friday Sale'; }
        echo '<aside class="byl-bf2026-announcement" data-byl-bf-banner hidden role="region" aria-label="Black Friday promotion">';
        echo '<a class="byl-bf2026-announcement__link" href="'.esc_url(home_url('/holiday/black-friday/')).'">';
        echo '<span class="byl-bf2026-announcement__headline">'.esc_html($headline).'</span>';
        echo '<span class="byl-bf2026-announcement__time" data-byl-bf-countdown aria-live="off"></span>';
        echo '</a></aside>';
    }
    private static function is_gift(WC_Product $p): bool {
        if (strtolower((string)$p->get_sku())==='gift') { return true; }
        $id=$p->is_type('variation') ? $p->get_parent_id() : $p->get_id();
        return function_exists('has_term') && has_term('gift','product_cat',$id);
    }
    private static function badge(WC_Product $product): string {
        if (!$product->is_on_sale() || self::is_gift($product)) { return ''; }
        $percent = 0;
        if (in_array($product->get_type(),['simple','variation'],true)) {
            $percent = self::discount_percent($product->get_regular_price(),$product->get_price());
        }
        if ($percent<=0 && !$product->is_type('variable')) { return ''; }
        $text=$percent>0 ? sprintf('Black Friday · %d%% OFF',$percent) : 'Black Friday Deal';
        return '<span class="byl-bf2026-sale-badge" data-byl-bf-badge data-byl-bf-percent="'.esc_attr((string)$percent).'">'.esc_html($text).'</span>';
    }
    public static function price_html($html,$product) {
        if (!self::enabled('product') || is_admin() || wp_doing_ajax() || !$product instanceof WC_Product) { return $html; }
        if (!(is_product() || is_shop() || is_product_category() || is_product_tag() || is_search())) { return $html; }
        if (!is_string($html) || strpos($html,'byl-bf2026-sale-badge')!==false) { return $html; }
        return $html.self::badge($product);
    }
    public static function cart_item_price($html,$item,$key) {
        if (!self::enabled('cart') || !is_array($item) || !isset($item['data']) || !$item['data'] instanceof WC_Product || !is_string($html)) { return $html; }
        if (isset($item['andy_campaign_pack_id']) || strpos($html,'byl-bf2026-sale-badge')!==false) { return $html; }
        return $html.self::badge($item['data']);
    }
    public static function mini_cart_quantity($html,$item,$key) {
        if (!self::enabled('cart') || !is_array($item) || !isset($item['data']) || !$item['data'] instanceof WC_Product || !is_string($html)) { return $html; }
        if (isset($item['andy_campaign_pack_id']) || strpos($html,'byl-bf2026-sale-badge')!==false) { return $html; }
        return $html.self::badge($item['data']);
    }
    public static function register_settings(): void {
        add_submenu_page('options-general.php','BF2026 Promotion UI','BF2026 Promotion UI','manage_options',self::SETTINGS_SLUG,[__CLASS__,'settings_page']);
    }
    public static function settings_page(): void {
        if (!current_user_can('manage_options')) { wp_die('Forbidden'); }
        $s=self::settings();
        echo '<div class="wrap"><h1>Black Friday · Promotion UI</h1><p>Display only. No changes to Woo prices, coupons, gifts, cart, orders or payments. Times are Los Angeles local.</p>';
        if (isset($_GET['updated'])) { echo '<div class="notice notice-success"><p>Saved.</p></div>'; }
        echo '<form action="'.esc_url(admin_url('admin-post.php')).'" method="post">';
        wp_nonce_field('byl_bf2026_promotion_ui_save');
        echo '<input type="hidden" name="action" value="byl_bf2026_promotion_ui_save">';
        foreach (['enabled'=>'Enable presentation','announcement'=>'Top countdown','product'=>'Product badges','cart'=>'Woo cart/mini-cart','drawer'=>'Dev drawer','sticky'=>'Sticky add-to-cart'] as $field=>$label) {
            echo '<p><label><input type="checkbox" name="'.esc_attr($field).'" value="yes" '.checked($s[$field],'yes',false).'> '.esc_html($label).'</label></p>';
        }
        foreach (['start'=>'Start (LA)','end'=>'End (LA)','headline'=>'Headline'] as $field=>$label) {
            echo '<p><label>'.esc_html($label).' <input class="regular-text" name="'.esc_attr($field).'" value="'.esc_attr((string)$s[$field]).'"></label></p>';
        }
        submit_button('Save display settings');
        echo '</form></div>';
    }
    public static function save_settings(): void {
        if (!current_user_can('manage_options')) { wp_die('Forbidden','',['response'=>403]); }
        check_admin_referer('byl_bf2026_promotion_ui_save');
        if (($_SERVER['REQUEST_METHOD']??'')!=='POST') { wp_die('POST required','',['response'=>405]); }
        $s=[];
        foreach (['enabled','announcement','product','cart','drawer','sticky'] as $key) {
            $s[$key]=isset($_POST[$key]) && is_string($_POST[$key]) && wp_unslash($_POST[$key])==='yes' ? 'yes':'no';
        }
        foreach (['start','end'] as $key) {
            $value=isset($_POST[$key]) && is_string($_POST[$key]) ? sanitize_text_field(wp_unslash($_POST[$key])):'';
            if (self::epoch($value)<=0) { wp_die('Invalid LA date YYYY-MM-DD HH:MM','',['response'=>422]); }
            $s[$key]=$value;
        }
        if (self::epoch($s['end'])<=self::epoch($s['start'])) { wp_die('Invalid time window','',['response'=>422]); }
        $s['headline']=isset($_POST['headline']) && is_string($_POST['headline']) ? substr(sanitize_text_field(wp_unslash($_POST['headline'])),0,100):'';
        update_option(self::OPTION,$s,false);
        wp_safe_redirect(add_query_arg(['page'=>self::SETTINGS_SLUG,'updated'=>'1'],admin_url('options-general.php')));
        exit;
    }
}
