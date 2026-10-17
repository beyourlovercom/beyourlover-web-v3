<?php
/**
 * WWW AffiliateWP → Dev Creator UI: fail-closed server-side read-only bridge.
 * No browser API credentials and no mutation/auto-linking.
 */
declare(strict_types=1);
defined('ABSPATH') || exit;

function byl_v3_creator_binding_proof(int $dev_user_id, int $www_user_id, int $affiliate_id): string {
    if (!defined('BYL_CREATOR_BINDING_KEY') || !is_string(BYL_CREATOR_BINDING_KEY) || strlen(BYL_CREATOR_BINDING_KEY) < 32) { return ''; }
    return hash_hmac('sha256', 'byl-creator-binding-v1|'.$dev_user_id.'|'.$www_user_id.'|'.$affiliate_id, BYL_CREATOR_BINDING_KEY);
}

/** Binding is created only after a trusted operator independently verifies both accounts. */
function byl_v3_creator_verified_binding(WP_User $user): ?array {
    if (!function_exists('get_user_meta')) { return null; }
    $mapping = get_user_meta((int) $user->ID, '_byl_creator_www_binding_v1', true);
    if (!is_array($mapping) || count(array_diff(['www_user_id','affiliate_id','proof'], array_keys($mapping))) > 0) { return null; }
    foreach (['www_user_id','affiliate_id'] as $key) {
        if (!(is_int($mapping[$key]) || is_string($mapping[$key]) && ctype_digit($mapping[$key])) || (int) $mapping[$key] <= 0) { return null; }
    }
    $proof = byl_v3_creator_binding_proof((int) $user->ID, (int) $mapping['www_user_id'], (int) $mapping['affiliate_id']);
    if (!$proof || !is_string($mapping['proof']) || !hash_equals($proof, $mapping['proof'])) { return null; }
    return ['www_user_id'=>(int)$mapping['www_user_id'], 'affiliate_id'=>(int)$mapping['affiliate_id']];
}

function byl_v3_creator_remote_snapshot(WP_User $user): ?array {
    if (!defined('BYL_CREATOR_REMOTE_ENABLED') || BYL_CREATOR_REMOTE_ENABLED !== true ||
        !defined('BYL_CREATOR_REMOTE_KEY_ID') || !defined('BYL_CREATOR_REMOTE_SIGN_KEY') ||
        !is_string(BYL_CREATOR_REMOTE_KEY_ID) || !preg_match('/^[A-Za-z0-9._-]{8,64}$/D', BYL_CREATOR_REMOTE_KEY_ID) ||
        !is_string(BYL_CREATOR_REMOTE_SIGN_KEY) || strlen(BYL_CREATOR_REMOTE_SIGN_KEY) < 32 ||
        !function_exists('wp_remote_get') || !function_exists('wp_remote_retrieve_response_code') ||
        !function_exists('wp_remote_retrieve_body')) { return null; }
    if (function_exists('user_can') && user_can($user, 'manage_options')) { return null; }
    $binding = byl_v3_creator_verified_binding($user);
    if (!$binding) { return null; }
    $args = [
        'dev_user_id'=>(int)$user->ID,
        'affiliate_id'=>$binding['affiliate_id'],
        'wp_user_id'=>$binding['www_user_id'],
        'ref_page'=>byl_v3_affiliate_page_number('byl_ref_page'),
        'pay_page'=>byl_v3_affiliate_page_number('byl_pay_page'),
    ];
    // Fixed host and path: no user-supplied URL or access tokens in query params.
    $path = '/wp-json/andy-core/v1/creator/history?'.http_build_query($args, '', '&', PHP_QUERY_RFC3986);
    $ts = (string) time();
    try { $nonce = bin2hex(random_bytes(16)); } catch (Throwable $e) { return null; }
    $sig = hash_hmac('sha256', "GET\n".$path."\n".$ts."\n".$nonce."\n".BYL_CREATOR_REMOTE_KEY_ID, BYL_CREATOR_REMOTE_SIGN_KEY);
    $response = wp_remote_get('https://www.beyourlover.com'.$path, [
        'timeout'=>7, 'redirection'=>0, 'sslverify'=>true, 'reject_unsafe_urls'=>true,
        'headers'=>[
            'Accept'=>'application/json',
            'X-BYL-Creator-Key'=>BYL_CREATOR_REMOTE_KEY_ID,
            'X-BYL-Creator-Time'=>$ts,
            'X-BYL-Creator-Nonce'=>$nonce,
            'X-BYL-Creator-Signature'=>$sig,
            'Cache-Control'=>'no-store',
        ],
    ]);
    if (function_exists('is_wp_error') && is_wp_error($response)) { return null; }
    if (!is_array($response) || wp_remote_retrieve_response_code($response) !== 200) { return null; }
    $payload = json_decode((string) wp_remote_retrieve_body($response), true);
    if (!is_array($payload) || ($payload['ok'] ?? false) !== true || !is_array($payload['data'] ?? null)) { return null; }
    $data = $payload['data'];
    if ((int)($data['affiliate_id'] ?? 0) !== $binding['affiliate_id'] ||
        (int)($data['wp_user_id'] ?? 0) !== $binding['www_user_id'] ||
        ($data['status'] ?? '') !== 'active') { return null; }
    $totals = $data['account'] ?? null;
    $currency = $data['currency'] ?? null;
    if (!is_array($totals) || !is_string($currency) || !preg_match('/^[A-Z]{3}$/D', $currency)) { return null; }
    $account = (object) ['earnings'=>null,'unpaid_earnings'=>null,'visits'=>null,'referrals'=>null];
    foreach (['earnings','unpaid_earnings','visits','referrals'] as $key) {
        if (isset($totals[$key]) && is_numeric($totals[$key]) && (float)$totals[$key] >= 0) { $account->$key = $totals[$key]; }
    }
    $rows = [];
    foreach (['referrals'=>12, 'payouts'=>4] as $resource=>$limit) {
        $raw = $data[$resource] ?? null;
        if (!is_array($raw) || count($raw) > $limit) { return null; }
        $rows[$resource] = [];
        foreach ($raw as $entry) {
            if (!is_array($entry) || (int)($entry['affiliate_id'] ?? 0) !== $binding['affiliate_id']) { return null; }
            $safe = array_intersect_key($entry, array_flip(['affiliate_id','referral_id','date','amount','status','currency']));
            $rows[$resource][] = (object) $safe;
        }
    }
    $referral_url = (string) ($data['referral_url'] ?? '');
    if ($referral_url !== '' && (!function_exists('wp_http_validate_url') ||
        !wp_http_validate_url($referral_url) ||
        wp_parse_url($referral_url, PHP_URL_HOST) !== 'www.beyourlover.com')) { $referral_url = ''; }
    return [
        'state'=>'active','user'=>$user,'id'=>$binding['affiliate_id'],
        'account'=>$account,'referrals'=>$rows['referrals'],'payouts'=>$rows['payouts'],'creatives'=>[],
        'referral_url'=>$referral_url,'currency'=>$currency,
        'referral_page'=>$args['ref_page'],'payout_page'=>$args['pay_page'],
        'more_referrals'=>($data['more_referrals'] ?? false) === true,
        'more_payouts'=>($data['more_payouts'] ?? false) === true,
        'remote_readonly'=>true,
    ];
}
