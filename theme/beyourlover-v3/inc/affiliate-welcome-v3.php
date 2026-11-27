<?php
/**
 * BYL Affiliate welcome V3: site-specific adapter, existing Connector/WP mail.
 * Explicit opt-in only; no new plugin, new endpoint, or global email setting.
 * The same two files can be included by the legacy child theme at a later,
 * separately approved WWW release. No live action is performed by this file alone.
 */
defined('ABSPATH') || exit;

final class BYL_Affiliate_Welcome_V3 {
    private const PREFIX = '/andy-core/v1/erp/';
    private const SUBJECT = 'Welcome to BeYourLover — your affiliate account is ready!';
    private static $scope = null;
    private static $new_user = 0;

    public static function enabled(): bool {
        return defined('BYL_AFFILIATE_WELCOME_V3_ENABLED') && BYL_AFFILIATE_WELCOME_V3_ENABLED === true
            && in_array(wp_parse_url(home_url('/'), PHP_URL_HOST), ['www.beyourlover.com','dev.beyourlover.com'], true);
    }

    private static function route($handler, $request, string $path, string $callback): bool {
        return $request->get_method() === 'POST' && $request->get_route() === self::PREFIX.$path
            && ($handler['callback'] ?? null) === ['YBY_Connector', $callback];
    }

    public static function before($response, $handler, $request) {
        if ($response !== null || !self::enabled() || !is_readable(__DIR__.'/affiliate-welcome-v3.html')
            || !class_exists('YBY_Connector_Affiliate_Bindings') || !class_exists('YBY_Connector_Coupon_Bindings')
            || !class_exists('WC_Coupon') || !function_exists('affwp_get_affiliate_rate')
            || !self::route($handler,$request,'affiliates/provision','dispatch_affiliate_provision')) return $response;
        $body = $request->get_json_params();
        if (!is_array($body) || ($body['existing_account_policy'] ?? '') !== 'reject'
            || ($body['requested_affiliate_status'] ?? '') !== 'active') return $response;
        // Connector itself authenticates and validates before creating anything.
        // The filter is scoped to this request and only to the approval event.
        self::$scope = $request;
        self::$new_user = 0;
        add_filter('affwp_notify_on_approval', [self::class,'suppress_approval'], PHP_INT_MAX);
        add_action('user_register', [self::class,'record_new_user'], PHP_INT_MAX, 1);
        return $response;
    }

    public static function suppress_approval($notify) {
        return self::$scope !== null ? false : $notify;
    }

    public static function record_new_user($id): void {
        if (self::$scope === null) return;
        $u = get_user_by('id',(int)$id);
        $body = self::$scope->get_json_params();
        if ($u && strcasecmp((string)$u->user_email,(string)($body['email'] ?? '')) === 0
            && (string)$u->user_login === (string)($body['preferred_username'] ?? '')) self::$new_user = (int)$id;
    }

    public static function after($response, $handler, $request) {
        $new = 0;
        if (self::$scope === $request) {
            $new = self::$new_user;
            self::$scope = null;
            self::$new_user = 0;
            remove_filter('affwp_notify_on_approval',[self::class,'suppress_approval'],PHP_INT_MAX);
            remove_action('user_register',[self::class,'record_new_user'],PHP_INT_MAX);
        }
        if (!self::enabled() || is_wp_error($response)) return $response;
        $data = is_object($response) && method_exists($response,'get_data') ? $response->get_data() : $response;
        if (!is_array($data) || ($data['ok'] ?? false) !== true || !is_array($data['data'] ?? null)) return $response;
        $result = $data['data'];
        $body = $request->get_json_params();
        $connection = (string)($data['connection_key'] ?? '');
        $kol = (int)($body['erp_kol_id'] ?? 0);
        if ($kol < 1 || $connection === '' || $connection !== (string)$request->get_header('X-YBY-Connection-Key')) return $response;
        try {
            if ($new > 0 && self::route($handler,$request,'affiliates/provision','dispatch_affiliate_provision')) {
                // Actual user_register observation excludes old idempotency replays / historical binds.
                if (($result['created_user'] ?? false) === true && ($result['created_affiliate'] ?? false) === true
                    && ($result['affiliate_status'] ?? '') === 'active' && (int)($result['wp_user_id'] ?? 0) === $new) {
                    $id = (int)($result['affiliate_id'] ?? 0);
                    $url = (string)($result['affiliate_referral_url'] ?? '');
                    $slug = (string)($result['affiliate_url_slug'] ?? '');
                    if ($id > 0 && $slug !== '' && self::valid_url($url) && self::binding_matches($connection,$kol,$id,$new)) {
                        add_option(self::key('pending',$id), ['connection'=>$connection,'kol'=>$kol,'user'=>$new,'url'=>$url,'slug'=>$slug], '', false);
                    } else self::log('enrollment_incomplete',$id);
                }
            } elseif (self::route($handler,$request,'coupons/provision','dispatch_coupon_provision')) {
                self::complete($connection,$kol,$result);
            }
        } catch (Throwable $e) {
            // A mail problem never rolls back an already-confirmed external account or coupon.
            self::log('adapter_exception',(int)($result['affiliate_id'] ?? 0));
        }
        return $response;
    }

    private static function key(string $kind,int $id): string { return 'byl_aff_welcome_v3_'.$kind.'_'.$id; }
    private static function log(string $code,int $id): void { error_log('[BYL Affiliate Welcome V3] '.$code.' affiliate_id='.$id); }

    private static function binding_matches(string $connection,int $kol,int $id,int $user): bool {
        if (!class_exists('YBY_Connector_Affiliate_Bindings')) return false;
        $b = YBY_Connector_Affiliate_Bindings::by_kol($connection,$kol);
        return is_array($b) && ($b['state'] ?? '') === 'ready'
            && (int)($b['affiliate_id'] ?? 0) === $id && (int)($b['wp_user_id'] ?? 0) === $user;
    }

    private static function valid_url(string $url): bool {
        return wp_parse_url($url,PHP_URL_SCHEME) === 'https'
            && wp_parse_url($url,PHP_URL_HOST) === wp_parse_url(home_url('/'),PHP_URL_HOST)
            && !wp_parse_url($url,PHP_URL_USER) && !wp_parse_url($url,PHP_URL_PASS);
    }

    private static function percent($n): ?string {
        if (!is_numeric($n) || !is_finite((float)$n) || (float)$n <= 0 || (float)$n > 100) return null;
        return rtrim(rtrim(number_format((float)$n,6,'.',''),'0'),'.').'%';
    }

    // WP add_option() uses ON DUPLICATE KEY UPDATE, which can overwrite an existing
    // row under a concurrent cache miss. Use the existing options unique key
    // with a no-update INSERT instead; no schema or new table is needed.
    private static function claim(string $key,array $value): bool {
        global $wpdb;
        $inserted = $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s,%s,'no')",
            $key,maybe_serialize($value)
        ));
        if ($inserted !== 1) return false;
        wp_cache_delete($key,'options');
        wp_cache_delete('notoptions','options');
        return true;
    }

    private static function complete(string $connection,int $kol,array $result): void {
        $id = (int)($result['affiliate_id'] ?? 0);
        $coupon_id = (int)($result['coupon_id'] ?? 0);
        if ($id < 1 || $coupon_id < 1 || get_option(self::key('dispatch',$id),false) !== false) return;
        $pending = get_option(self::key('pending',$id),false);
        if (!is_array($pending) || ($pending['connection'] ?? '') !== $connection || (int)($pending['kol'] ?? 0) !== $kol) return;
        $user_id = (int)($pending['user'] ?? 0);
        if (!self::binding_matches($connection,$kol,$id,$user_id)
            || !class_exists('YBY_Connector_Coupon_Bindings') || !class_exists('WC_Coupon')
            || !function_exists('affwp_get_affiliate') || !function_exists('affwp_get_affiliate_rate')
            || !function_exists('affwp_get_affiliate_rate_type') || !function_exists('affwp_email_notification_enabled')
            || !function_exists('affwp_get_affiliate_referral_url') || !function_exists('affwp_get_affiliate_meta')) {
            self::log('provider_unavailable',$id); return;
        }
        $normalized = (string)($result['normalized_code'] ?? '');
        $b = YBY_Connector_Coupon_Bindings::by_code($normalized);
        if (!is_array($b) || ($b['state'] ?? '') !== 'ready' || ($b['connection_key'] ?? '') !== $connection
            || (int)($b['erp_kol_id'] ?? 0) !== $kol || (int)($b['affiliate_id'] ?? 0) !== $id
            || (int)($b['coupon_id'] ?? 0) !== $coupon_id) { self::log('coupon_binding_mismatch',$id); return; }
        $a = affwp_get_affiliate($id);
        $u = get_user_by('id',$user_id);
        $c = new WC_Coupon($coupon_id);
        $code = (string)$c->get_code();
        $expiry = $c->get_date_expires();
        $discount = self::percent($c->get_amount());
        // AffiliateWP's non-formatted percentage rate is a fractional value.
        $rate = $a && !is_wp_error($a) ? affwp_get_affiliate_rate($id) : null;
        $commission = is_numeric($rate) ? self::percent((float)$rate*100) : null;
        if (!$u || !$a || is_wp_error($a) || (int)$a->user_id !== $user_id || $a->status !== 'active'
            || !is_email($u->user_email) || trim((string)$u->user_login) === '' || trim((string)$u->display_name) === ''
            || (int)$c->get_id() !== $coupon_id || $code === '' || strtolower($code) !== $normalized
            || (int)get_post_meta($coupon_id,'affwp_discount_affiliate',true) !== $id
            || $c->get_status() !== 'publish' || ($expiry && $expiry->getTimestamp() <= time())
            || $c->get_discount_type() !== 'percent' || $discount === null
            || affwp_get_affiliate_rate_type($id) !== 'percentage' || $commission === null
            || !self::valid_url((string)$pending['url'])) { self::log('data_not_ready',$id); return; }
        $slug = (string)($pending['slug'] ?? '');
        $custom = $slug !== (string)$u->user_login;
        if ($slug === '' || ($custom && (string)affwp_get_affiliate_meta($id,'custom_slug',true) !== $slug)
            || affwp_get_affiliate_referral_url(['affiliate_id'=>$id,'format'=>$custom?'slug':'username']) !== $pending['url']) {
            self::log('referral_changed',$id); return;
        }
        if (!affwp_email_notification_enabled('affiliate_application_accepted_email')
            || get_user_meta($user_id,'affwp_disable_affiliate_email',true)) { self::log('notifications_disabled',$id); return; }
        // Atomic unique option claim: fail closed on ambiguous transport or interrupted send.
        // Never retry a claimed dispatch automatically: SMTP acceptance is not proof of delivery.
        $dispatch = self::key('dispatch',$id);
        if (!self::claim($dispatch,['state'=>'sending','at'=>time(),'coupon_id'=>$coupon_id])) return;
        $state = 'needs_review';
        try {
            $reset = get_password_reset_key($u);
            if (is_wp_error($reset) || !is_string($reset) || $reset === '') return;
            $password_url = network_site_url('wp-login.php?action=rp&key='.rawurlencode($reset).'&login='.rawurlencode($u->user_login),'login');
            $html = self::render([
                'name'=>(string)$u->display_name,'username'=>(string)$u->user_login,
                'password_url'=>$password_url,'login_url'=>home_url('/affiliate-login/'),
                'discount_rate'=>$discount,'commission_rate'=>$commission,'coupon'=>$code,
                'referral_url'=>(string)$pending['url'],
            ]);
            if ($html === null) return;
            $ok = wp_mail($u->user_email,self::SUBJECT,$html,[
                'Content-Type: text/html; charset=UTF-8',
                'From: BeYourLover <pr@beyourlover.com>',
                'Reply-To: pr@beyourlover.com',
            ]);
            $state = $ok ? 'mail_accepted' : 'needs_review';
        } finally {
            if (!update_option($dispatch,['state'=>$state,'at'=>time(),'coupon_id'=>$coupon_id],false)) self::log('dispatch_state_write_failed',$id);
            self::log($state,$id);
        }
    }

    public static function render(array $values): ?string {
        $html = file_get_contents(__DIR__.'/affiliate-welcome-v3.html');
        if (!is_string($html)) return null;
        $required = ['name','username','password_url','login_url','discount_rate','commission_rate','coupon','referral_url'];
        foreach ($required as $key) {
            if (!isset($values[$key]) || !is_string($values[$key]) || trim($values[$key]) === '') return null;
            if (substr($key,-4)==='_url' && !self::valid_url($values[$key])) return null;
        }
        $html = preg_replace_callback('/\{\{([a-z_]+)\}\}/',static function($m) use($values) {
            return isset($values[$m[1]]) ? htmlspecialchars($values[$m[1]],ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') : $m[0];
        },$html);
        return is_string($html) && !preg_match('/\{\{[a-z_]+\}\}/',$html) ? $html : null;
    }
}
add_filter('rest_request_before_callbacks',[BYL_Affiliate_Welcome_V3::class,'before'],10,3);
add_filter('rest_request_after_callbacks',[BYL_Affiliate_Welcome_V3::class,'after'],10,3);
