<?php
/**
 * BeYourLover Creator dashboard — visual presentation of the signed-in
 * affiliate's existing AffiliateWP records. Never render demo financial data.
 */
declare(strict_types=1);
defined('ABSPATH') || exit;
$aff = byl_v3_affiliate_snapshot();
$user = $aff['user'];
$active = $aff['state'] === 'active';
$account = $aff['account'];
$currency = (string) $aff['currency'];
$first_name = $user instanceof WP_User ? (string) $user->first_name : '';
$name = $first_name ?: ($user instanceof WP_User ? (string) $user->display_name : '');
$name = $name ?: 'Creator';
$initial = function_exists('mb_substr') ? mb_substr($name, 0, 1) : substr($name, 0, 1);
$nav = [
    ['Dashboard','overview','house'],
    ['Performance','performance','chart'],
    ['Referral Orders','referrals','bag'],
    ['Promo Codes','sharing','tag'],
    ['Creatives','creatives','image'],
    ['Payouts','payouts','credit-card'],
    ['Account Details','account','user'],
    ['Support','support','help'],
];
$icon_paths = [
    'house'=>'<path d="m3 10 9-7 9 7v10H3z"/><path d="M9 20v-7h6v7"/>',
    'chart'=>'<path d="M3 20h18M5 16v4m4-10v10m4-16v16m4-8v8"/>',
    'bag'=>'<path d="M5 8h14l1 13H4L5 8z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/>',
    'tag'=>'<path d="M20 13 12 21l-9-9V4h8z"/><circle cx="8" cy="8" r="1"/>',
    'image'=>'<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8" cy="9" r="1"/><path d="m4 18 6-6 4 4 2-2 5 5"/>',
    'credit-card'=>'<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/>',
    'user'=>'<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
    'help'=>'<circle cx="12" cy="12" r="10"/><path d="M9 9a3 3 0 0 1 6 1c0 2-3 2-3 5m0 3v.1"/>',
    'logout'=>'<path d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h7"/><path d="m15 17 5-5-5-5m5 5H9"/>',
];
$creator_icon = static function (string $key) use ($icon_paths): string {
    return '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($icon_paths[$key] ?? '') . '</svg>';
};
$asset_uri = rtrim(get_stylesheet_directory_uri(), '/') . '/assets/images/';

$metrics = $active ? [
    ['Paid Earnings', $account->earnings ?? null, true, 'AffiliateWP paid total', '$'],
    ['Pending Commission', $account->unpaid_earnings ?? null, true, 'Current unpaid balance', 'coins'],
    ['Total Clicks', $account->visits ?? null, false, 'Tracked referral visits', 'cursor'],
    ['Conversion Rate', null, false, 'Not independently verified', 'users'],
    ['Total Referred Orders', $account->referrals ?? null, false, 'All-status referral records', 'bag'],
] : [];
$referrals = $aff['referrals'];
$payouts = $aff['payouts'];
$referral_url = $aff['referral_url'];
$visual = $asset_uri . 'creator-v2-hero-photo.webp';
$sign_in = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/');
get_header();
?>
<main id="main-content" class="byl-affiliate byl-affiliate-v2" data-byl-affiliate-v1>
    <header class="byl-v2-topbar" aria-label="BeYourLover creator navigation">
        <a class="byl-v2-wordmark" href="<?php echo esc_url(home_url('/')); ?>" aria-label="BeYourLover homepage">BEYOURLOVER</a>
        <nav class="byl-v2-top-links" aria-label="Shop navigation">
            <a href="<?php echo esc_url(home_url('/new-arrivals/')); ?>">New In</a>
            <a href="<?php echo esc_url(home_url('/sex-toys/')); ?>">Shop</a>
            <a href="<?php echo esc_url(home_url('/best-sellers/')); ?>">Best Sellers</a>
            <a href="<?php echo esc_url(home_url('/sex-toys-for-women/')); ?>">Collections</a>
            <a href="<?php echo esc_url(home_url('/about-us/')); ?>">About</a>
        </nav>
        <div class="byl-v2-top-actions">
            <a href="<?php echo esc_url(home_url('/?s=&post_type=product')); ?>" aria-label="Search" class="byl-v2-search">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="10.7" cy="10.7" r="7"/><path d="m16 16 5 5"/></svg>
            </a>
            <a href="<?php echo esc_url($sign_in); ?>" aria-label="My account"><?php echo $creator_icon('user'); ?></a>
            <a href="<?php echo esc_url(function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/')); ?>" aria-label="Cart"><?php echo $creator_icon('bag'); ?></a>
        </div>
    </header>
<?php if (!$active) : ?>
    <section class="byl-affiliate-gate" aria-labelledby="byl-affiliate-gate-title">
        <p class="byl-affiliate-eyebrow">BeYourLover Creator Community</p>
        <h1 id="byl-affiliate-gate-title"><?php echo $aff['state'] === 'guest' ? 'Your creator space awaits.' : 'Creator access is pending verification.'; ?></h1>
        <p><?php echo $aff['state'] === 'guest' ? 'Sign in with your approved affiliate account to view commissions, referral links and payouts.' : 'We cannot verify this sign-in for the new creator dashboard yet. Your existing AffiliateWP account and historical commissions remain unchanged. Please contact your affiliate manager for access.'; ?></p>
        <a class="byl-affiliate-button" href="<?php echo esc_url($sign_in); ?>"><?php echo $aff['state'] === 'guest' ? 'Sign In' : 'Go to My Account'; ?> <span aria-hidden="true">→</span></a>
    </section>
<?php else : ?>
    <div class="byl-affiliate-layout">
        <aside class="byl-affiliate-rail" aria-label="Creator account navigation">
            <div class="byl-affiliate-profile">
                <img class="byl-affiliate-avatar byl-v2-profile-art" src="<?php echo esc_url($asset_uri.'creator-v2-avatar.webp'); ?>" width="78" height="78" alt="" loading="eager">
                <div><strong><?php echo esc_html($name); ?></strong><small>Affiliate Member</small><span class="byl-affiliate-pill">PRO CREATOR</span></div>
            </div>
            <nav class="byl-affiliate-menu" aria-label="Creator dashboard">
                <?php foreach ($nav as $i => $item): ?>
                    <a href="#<?php echo esc_attr($item[1]); ?>"<?php echo $i === 0 ? ' aria-current="location"' : ''; ?>><span class="byl-affiliate-menu-icon"><?php echo $creator_icon($item[2]); ?></span><?php echo esc_html($item[0]); ?></a>
                <?php endforeach; ?>
            </nav>
            <div class="byl-affiliate-rail-foot">
                <a href="<?php echo esc_url(byl_v3_account_url()); ?>">My Shopping Account <span aria-hidden="true">↗</span></a>
                <a href="<?php echo esc_url(home_url('/customer-help/')); ?>">Help &amp; Support <span aria-hidden="true">↗</span></a>
                <a class="byl-v2-logout" href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>"><?php echo $creator_icon('logout'); ?> Log Out</a>
            </div>
            <div class="byl-affiliate-rail-quote"><img src="<?php echo esc_url($asset_uri.'creator-v2-sidebar-poster.webp'); ?>" width="217" height="350" alt="Empower Pleasure. Share Confidence." loading="lazy"></div>
        </aside>
        <div class="byl-affiliate-main">
            <?php if (wp_parse_url(home_url('/'), PHP_URL_HOST) === 'dev.beyourlover.com' && empty($aff['remote_readonly'])) : ?>
                <p role="status" class="byl-affiliate-footnote">Dev test data only — this account uses local AffiliateWP records. WWW historical commissions and payouts are not connected yet.</p>
            <?php endif; ?>
            <section class="byl-affiliate-hero" id="overview" aria-labelledby="byl-affiliate-title">
                <div class="byl-affiliate-hero-text">
                    <h1 id="byl-affiliate-title">Hi, <?php echo esc_html($name); ?></h1>
                    <p class="byl-affiliate-hero-subtitle"><em>Your affiliate dashboard</em></p>
                    <p class="byl-affiliate-hero-description">Thank you for being part of the BEYOURLOVER creator community.<br>Together, we’re making pleasure a more open and empowered conversation.</p>
                </div>
                <img class="byl-affiliate-hero-image" src="<?php echo esc_url($visual); ?>" width="492" height="189" alt="" loading="eager" fetchpriority="high">
                <div class="byl-v2-hero-slogan" aria-label="Brand statement">REAL PEOPLE<br>REAL PLEASURE<br>A BRIGHTER TOMORROW<span></span></div>
            </section>
            <section class="byl-affiliate-stats" aria-label="AffiliateWP account totals">
                <?php foreach ($metrics as $i => $metric): ?>
                    <article class="byl-affiliate-stat">
                        <span class="byl-affiliate-stat-icon" aria-hidden="true"><?php echo $i === 0 ? '$' : ($i === 1 ? '≋' : $creator_icon($metric[4] === 'cursor' ? 'chart' : ($metric[4] === 'users' ? 'user' : 'bag'))); ?></span>
                        <div><p><?php echo esc_html($metric[0]); ?></p><strong><?php echo esc_html(byl_v3_affiliate_metric($metric[1], $metric[2], $currency)); ?></strong><small><?php echo esc_html($metric[3]); ?></small></div>
                    </article>
                <?php endforeach; ?>
            </section>
            <div class="byl-affiliate-panels">
                <section class="byl-affiliate-card byl-affiliate-performance" id="performance" aria-labelledby="byl-performance-title">
                    <div class="byl-affiliate-card-top"><h2 id="byl-performance-title">Earnings Performance</h2><span class="byl-affiliate-tag">Recent Referrals ▾</span></div>
                    <?php
                    $bars = array_reverse(array_slice($referrals, 0, 12));
                    $max = 0.0;
                    $chart_currencies = [];
                    foreach ($bars as $entry) {
                        $max = max($max, is_numeric($entry->amount ?? null) ? (float) $entry->amount : 0.0);
                        $chart_currencies[] = strtoupper((string) ($entry->currency ?? $currency));
                    }
                    $chart_comparable = count(array_unique($chart_currencies)) <= 1;
                    ?>
                    <?php if (count($bars) >= 3 && $max > 0 && $chart_comparable) : ?>
                        <p class="byl-affiliate-muted">Commission amounts in your latest <?php echo esc_html((string) count($bars)); ?> referrals, not a revenue forecast.</p>
                        <div class="byl-v2-chart-frame">
                            <div class="byl-v2-y-axis" aria-hidden="true">
                                <?php for ($tick = 4; $tick >= 0; $tick--): ?>
                                    <span><?php echo esc_html(byl_v3_affiliate_money($max * $tick / 4, $currency)); ?></span>
                                <?php endfor; ?>
                            </div>
                            <div class="byl-v2-chart-plot">
                                <div class="byl-affiliate-chart" role="img" aria-label="Recent referral commissions: <?php echo esc_attr((string) count($bars)); ?> records">
                                    <?php foreach ($bars as $entry):
                                        $amount = is_numeric($entry->amount ?? null) ? max(0.0, (float) $entry->amount) : 0.0;
                                        $height = round(max(4, 100 * $amount / $max));
                                    ?>
                                        <div class="byl-affiliate-bar" style="--byl-bar-height:<?php echo esc_attr((string) $height); ?>%" title="<?php echo esc_attr(byl_v3_affiliate_money($amount, (string) ($entry->currency ?? $currency))); ?>"><span></span></div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="byl-v2-chart-dates" aria-hidden="true">
                                    <?php for ($label = 0; $label < 5; $label++):
                                        $n = (int) round($label * (count($bars) - 1) / 4);
                                        $day = !empty($bars[$n]->date) ? mysql2date('M j', (string) $bars[$n]->date) : '';
                                    ?>
                                        <span><?php echo esc_html($day); ?></span>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                        <p class="byl-affiliate-chart-caption">Real AffiliateWP referral commissions, including unpaid records. No estimated earnings.</p>
                    <?php else : ?>
                        <div class="byl-affiliate-empty-chart"><span aria-hidden="true">↗</span><p><?php echo $chart_comparable ? 'More referral history is needed for a meaningful trend chart.' : 'Multiple currencies cannot be compared on one chart.'; ?></p><small>Only comparable AffiliateWP amounts are plotted.</small></div>
                    <?php endif; ?>
                </section>
                <section class="byl-affiliate-card byl-affiliate-sharing" id="sharing" aria-labelledby="byl-sharing-title">
                    <div class="byl-affiliate-sharing-top">
                        <h2 id="byl-sharing-title">Your Exclusive Code</h2>
                        <p>Share your code and earn commission on every qualifying order.</p>
                        <div class="byl-v2-code-unavailable">Code pending verification</div>
                        <img class="byl-v2-promo-photo" src="<?php echo esc_url($asset_uri.'creator-v2-promo-product.webp'); ?>" width="200" height="143" alt="" loading="lazy">
                    </div>
                    <?php if ($referral_url): ?>
                        <div class="byl-affiliate-link-wrap">
                            <label for="byl-affiliate-link">OR SHARE YOUR REFERRAL LINK</label>
                            <div class="byl-affiliate-copy-row"><input id="byl-affiliate-link" type="text" readonly value="<?php echo esc_attr($referral_url); ?>"><button type="button" class="byl-affiliate-copy" data-byl-copy="#byl-affiliate-link">Copy Link</button><button type="button" class="byl-v2-share-button" data-byl-share="#byl-affiliate-link">Share</button></div>
                            <a class="byl-affiliate-open-link" href="<?php echo esc_url($referral_url); ?>" rel="nofollow">Open referral URL <span aria-hidden="true">↗</span></a>
                        </div>
                    <?php else : ?>
                        <div class="byl-affiliate-link-missing">Your verified referral link is currently unavailable. Please contact your affiliate manager.</div>
                    <?php endif; ?>
                    <?php if (!empty($aff['remote_readonly'])): ?>
                        <p class="byl-affiliate-coupon-note">Promotion codes from your WWW affiliate account are not yet available in this read-only dashboard. Contact your affiliate manager to confirm an assigned code; no Dev test codes are shown.</p>
                    <?php else:
                        $coupon_html = shortcode_exists('affiliate_coupons') ? trim((string) do_shortcode('[affiliate_coupons]')) : '';
                    ?>
                        <?php if ($coupon_html !== ''): ?>
                            <div class="byl-affiliate-assigned-coupons"><h3>Your assigned promo codes</h3><?php echo wp_kses_post($coupon_html); ?></div>
                        <?php else: ?>
                            <p class="byl-affiliate-coupon-note">Your verified promotional codes will appear here when assigned through AffiliateWP. No sample code is shown.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </section>
                <section class="byl-affiliate-card byl-affiliate-referrals" id="referrals" aria-labelledby="byl-referrals-title">
                    <div class="byl-affiliate-card-top"><h2 id="byl-referrals-title">Recent Referral Orders</h2><button type="button" class="byl-v2-view-all" data-byl-expand-referrals aria-expanded="false" aria-controls="byl-referral-rows">View All Orders →</button></div>
                    <?php if ($referrals): ?>
                        <div class="byl-affiliate-table-wrap"><table class="byl-affiliate-table"><thead><tr><th scope="col">Referral #</th><th scope="col">Date</th><th scope="col">Commission</th><th scope="col">Status</th></tr></thead><tbody id="byl-referral-rows">
                        <?php foreach ($referrals as $entry):
                            $date = !empty($entry->date) ? mysql2date('M j, Y', (string) $entry->date) : '—';
                            $status = strtolower((string) ($entry->status ?? 'pending'));
                            $status = in_array($status, ['pending','unpaid','paid','rejected'], true) ? $status : 'unknown';
                        ?>
                            <tr><td>#<?php echo esc_html((string) ($entry->referral_id ?? '—')); ?></td><td><?php echo esc_html($date); ?></td><td><?php echo esc_html(byl_v3_affiliate_money($entry->amount ?? null, (string) ($entry->currency ?? $currency))); ?></td><td><span class="byl-affiliate-status byl-affiliate-status--<?php echo esc_attr($status); ?>"><?php echo esc_html(ucfirst($status)); ?></span><span class="byl-v2-row-chevron" aria-hidden="true">›</span></td></tr>
                        <?php endforeach; ?>
                        </tbody></table></div>
                    <?php else: ?>
                        <p class="byl-affiliate-empty">No referrals have been recorded for this affiliate yet.</p>
                    <?php endif; ?>
                    <div class="byl-affiliate-history-nav" aria-label="Referral history navigation">
                        <?php if ($aff['referral_page'] > 1): ?><a href="<?php echo esc_url(add_query_arg('byl_ref_page', $aff['referral_page'] - 1, home_url('/my-affiliate/')).'#referrals'); ?>">← Newer referrals</a><?php endif; ?>
                        <span>History page <?php echo esc_html((string) $aff['referral_page']); ?></span>
                        <?php if ($aff['more_referrals']): ?><a href="<?php echo esc_url(add_query_arg('byl_ref_page', $aff['referral_page'] + 1, home_url('/my-affiliate/')).'#referrals'); ?>">Older referrals →</a><?php endif; ?>
                    </div>
                    <p class="byl-affiliate-footnote">AffiliateWP historical records are retrieved page by page for your own account only.</p>
                </section>
                <section class="byl-affiliate-card byl-affiliate-payouts" id="payouts" aria-labelledby="byl-payouts-title">
                    <div class="byl-affiliate-card-top"><h2 id="byl-payouts-title">Payouts</h2><a class="byl-v2-text-link" href="<?php echo !empty($aff['remote_readonly']) ? 'https://www.beyourlover.com/affiliate-area/' : esc_url(home_url('/affiliate-area/')); ?>" rel="noopener noreferrer">View All →</a></div>
                    <div class="byl-affiliate-payout-summary">
                        <span aria-hidden="true"><?php echo $creator_icon('credit-card'); ?></span>
                        <div><small>Next payout date</small><strong class="byl-v2-no-date">Not scheduled</strong></div>
                        <div class="byl-v2-unpaid-cell"><small>Current unpaid balance</small><strong><?php echo esc_html(byl_v3_affiliate_metric($account->unpaid_earnings ?? null, true, $currency)); ?></strong></div>
                    </div>
                    <h3>Recent Payouts</h3>
                    <?php if ($payouts): ?>
                        <ul class="byl-affiliate-payout-list">
                        <?php foreach ($payouts as $entry): ?>
                            <li><span><?php echo esc_html(!empty($entry->date) ? mysql2date('M j, Y', (string) $entry->date) : '—'); ?></span><strong><?php echo esc_html(byl_v3_affiliate_money($entry->amount ?? null, $currency)); ?></strong><small><?php echo esc_html(ucfirst((string) ($entry->status ?? 'recorded'))); ?></small></li>
                        <?php endforeach; ?>
                        </ul>
                    <?php else: ?><p class="byl-affiliate-empty">No payout records are available.</p><?php endif; ?>
                    <div class="byl-affiliate-history-nav" aria-label="Payout history navigation">
                        <?php if ($aff['payout_page'] > 1): ?><a href="<?php echo esc_url(add_query_arg('byl_pay_page', $aff['payout_page'] - 1, home_url('/my-affiliate/')).'#payouts'); ?>">← Newer payouts</a><?php endif; ?>
                        <span>History page <?php echo esc_html((string) $aff['payout_page']); ?></span>
                        <?php if ($aff['more_payouts']): ?><a href="<?php echo esc_url(add_query_arg('byl_pay_page', $aff['payout_page'] + 1, home_url('/my-affiliate/')).'#payouts'); ?>">Older payouts →</a><?php endif; ?>
                    </div>
                    <p class="byl-affiliate-footnote">Payout dates are not estimated or promised by this dashboard.</p>
                </section>
                <section class="byl-affiliate-card byl-affiliate-creatives" id="creatives" aria-labelledby="byl-creatives-title">
                    <div class="byl-affiliate-card-top"><h2 id="byl-creatives-title">Creatives &amp; Assets</h2><span class="byl-v2-text-link">View All Assets →</span></div>
                    <div class="byl-v2-creative-filters" role="group" aria-label="Preview creative category">
                        <button type="button" class="byl-v2-filter-active" data-byl-asset-filter="all" aria-pressed="true">All</button>
                        <button type="button" data-byl-asset-filter="banners" aria-pressed="false">Banners</button>
                        <button type="button" data-byl-asset-filter="product" aria-pressed="false">Product Images</button>
                        <button type="button" data-byl-asset-filter="lifestyle" aria-pressed="false">Lifestyle</button>
                        <button type="button" data-byl-asset-filter="social" aria-pressed="false">Social Media</button>
                        <button type="button" data-byl-asset-filter="videos" aria-pressed="false">Videos</button>
                    </div>
                    <?php if ($aff['creatives']): ?>
                        <div class="byl-affiliate-creative-grid">
                        <?php foreach ($aff['creatives'] as $creative):
                            $media = (string) ($creative->image ?? $creative->image_url ?? '');
                            $host = wp_parse_url($media, PHP_URL_HOST);
                            $safe = is_string($host) && in_array(strtolower($host), ['img.beyourlover.com','www.beyourlover.com','dev.beyourlover.com'], true) && wp_http_validate_url($media);
                        ?>
                            <article class="byl-affiliate-creative-item">
                                <?php if ($safe): ?><img src="<?php echo esc_url($media); ?>" alt="" loading="lazy"><?php else: ?><div class="byl-affiliate-creative-fallback">BeYourLover</div><?php endif; ?>
                                <strong><?php echo esc_html((string) ($creative->name ?? 'Creator asset')); ?></strong>
                            </article>
                        <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="byl-v2-creative-previews" aria-label="Concept imagery from approved dashboard design">
                            <img data-byl-asset-type="banners" src="<?php echo esc_url($asset_uri.'creator-v2-creative-confidence.webp'); ?>" width="174" height="106" alt="BeYourLover confidence design preview" loading="lazy">
                            <img data-byl-asset-type="product" src="<?php echo esc_url($asset_uri.'creator-v2-creative-product.webp'); ?>" width="130" height="106" alt="Product photograph design preview" loading="lazy">
                            <img data-byl-asset-type="social" src="<?php echo esc_url($asset_uri.'creator-v2-creative-message.webp'); ?>" width="116" height="106" alt="Brand message design preview" loading="lazy">
                            <img data-byl-asset-type="lifestyle" src="<?php echo esc_url($asset_uri.'creator-v2-creative-lifestyle.webp'); ?>" width="179" height="106" alt="Lifestyle photograph design preview" loading="lazy">
                        </div>
                        <p class="byl-v2-no-assets" hidden>No design previews for this category yet.</p>
                        <p class="byl-v2-concept-disclaimer">Visual previews from the approved design — not yet released as downloadable affiliate campaign assets.</p>
                    <?php endif; ?>
                </section>
                <section class="byl-affiliate-card byl-affiliate-support" id="account" aria-labelledby="byl-account-title">
                    <div id="support" class="byl-v2-support-anchor" aria-hidden="true"></div>
                    <img class="byl-v2-support-photo" src="<?php echo esc_url($asset_uri.'creator-v2-support.webp'); ?>" width="205" height="194" alt="" loading="lazy">
                    <div class="byl-v2-support-content">
                        <h2 id="byl-account-title"><span class="byl-v2-support-icon"><?php echo $creator_icon('help'); ?></span> Help &amp; Affiliate Support</h2>
                        <p class="byl-v2-support-lead">Have questions? We're here for you.</p>
                        <p>Get help with your account, commissions, creatives, or anything else about the BEYOURLOVER affiliate program.</p>
                        <a class="byl-v2-contact-button" href="<?php echo esc_url(home_url('/customer-help/')); ?>">✉ &nbsp; Contact Affiliate Support</a>
                        <details class="byl-v2-account-links">
                            <summary>Account &amp; payout settings</summary>
                            <a href="<?php echo esc_url(byl_v3_account_url()); ?>">My Shopping Account ↗</a>
                            <?php if (!empty($aff['remote_readonly'])): ?>
                                <a href="https://www.beyourlover.com/affiliate-area/" rel="noopener noreferrer">WWW affiliate payout preferences (WWW login required) ↗</a>
                            <?php else: ?>
                                <a href="<?php echo esc_url(home_url('/affiliate-area/')); ?>">Dev affiliate test settings ↗</a>
                            <?php endif; ?>
                        </details>
                    </div>
                </section>
            </div>
        </div>
    </div>
    <nav class="byl-affiliate-bottom-nav" aria-label="Mobile creator navigation">
        <a href="#overview" aria-label="Dashboard"><span aria-hidden="true">⌂</span>Home</a>
        <a href="#performance" aria-label="Performance"><span aria-hidden="true">↗</span>Stats</a>
        <a href="#sharing" aria-label="Referral link"><span aria-hidden="true">◇</span>Share</a>
        <a href="#referrals" aria-label="Referrals"><span aria-hidden="true">▣</span>Orders</a>
        <a href="#payouts" aria-label="Payouts"><span aria-hidden="true">◉</span>Payouts</a>
    </nav>
<?php endif; ?>
</main>
<?php get_footer(); ?>
