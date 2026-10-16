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
    ['Dashboard','overview','⌂'],
    ['Performance','performance','↗'],
    ['Referral Orders','referrals','▣'],
    ['Promo & Links','sharing','◇'],
    ['Creatives','creatives','▧'],
    ['Payouts','payouts','◉'],
    ['Account Details','account','♙'],
];
$metrics = $active ? [
    ['Paid Earnings', $account->earnings ?? null, true, 'All-time recorded by AffiliateWP'],
    ['Unpaid Commission', $account->unpaid_earnings ?? null, true, 'Subject to approval and payout rules'],
    ['Total Visits', $account->visits ?? null, false, 'AffiliateWP tracked visits'],
    ['Total Referrals', $account->referrals ?? null, false, 'AffiliateWP recorded referrals'],
] : [];
$referrals = $aff['referrals'];
$payouts = $aff['payouts'];
$referral_url = $aff['referral_url'];
$visual = get_stylesheet_directory_uri().'/assets/images/affiliate-hero.jpg';
$sign_in = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/');
get_header();
?>
<main id="main-content" class="byl-affiliate" data-byl-affiliate-v1>
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
                <span class="byl-affiliate-avatar" aria-hidden="true"><?php echo esc_html(strtoupper($initial)); ?></span>
                <div><strong><?php echo esc_html($name); ?></strong><small>Affiliate Member</small><span class="byl-affiliate-pill">CREATOR</span></div>
            </div>
            <nav class="byl-affiliate-menu" aria-label="Creator dashboard">
                <?php foreach ($nav as $i => $item): ?>
                    <a href="#<?php echo esc_attr($item[1]); ?>"<?php echo $i === 0 ? ' aria-current="location"' : ''; ?>><span class="byl-affiliate-menu-icon" aria-hidden="true"><?php echo esc_html($item[2]); ?></span><?php echo esc_html($item[0]); ?></a>
                <?php endforeach; ?>
            </nav>
            <div class="byl-affiliate-rail-foot">
                <a href="<?php echo esc_url(byl_v3_account_url()); ?>">My Shopping Account <span aria-hidden="true">↗</span></a>
                <a href="<?php echo esc_url(home_url('/customer-help/')); ?>">Help & Support <span aria-hidden="true">↗</span></a>
                <a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>">Log Out <span aria-hidden="true">↗</span></a>
            </div>
            <div class="byl-affiliate-rail-quote"><em>Empower Pleasure.<br>Share Confidence.</em><p>BeYourLover CREATOR COMMUNITY</p></div>
        </aside>
        <div class="byl-affiliate-main">
            <?php if (wp_parse_url(home_url('/'), PHP_URL_HOST) === 'dev.beyourlover.com' && empty($aff['remote_readonly'])) : ?>
                <p role="status" class="byl-affiliate-footnote">Dev test data only — this account uses local AffiliateWP records. WWW historical commissions and payouts are not connected yet.</p>
            <?php endif; ?>
            <section class="byl-affiliate-hero" id="overview" aria-labelledby="byl-affiliate-title">
                <div class="byl-affiliate-hero-text">
                    <p class="byl-affiliate-eyebrow">YOUR CREATOR SPACE</p>
                    <h1 id="byl-affiliate-title">Hi, <?php echo esc_html($name); ?><span class="byl-affiliate-hero-dot">.</span></h1>
                    <p class="byl-affiliate-hero-subtitle"><em>Your affiliate dashboard</em></p>
                    <p class="byl-affiliate-hero-description">Welcome to the BeYourLover creator community. Your links, referrals and commissions, all in one place.</p>
                </div>
                <img class="byl-affiliate-hero-image" src="<?php echo esc_url($visual); ?>" alt="" loading="eager" fetchpriority="high">
            </section>
            <section class="byl-affiliate-stats" aria-label="AffiliateWP account totals">
                <?php foreach ($metrics as $i => $metric): ?>
                    <article class="byl-affiliate-stat">
                        <span class="byl-affiliate-stat-icon" aria-hidden="true"><?php echo esc_html(['$', '◎', '↗', '▢'][$i]); ?></span>
                        <div><p><?php echo esc_html($metric[0]); ?></p><strong><?php echo esc_html(byl_v3_affiliate_metric($metric[1], $metric[2], $currency)); ?></strong><small><?php echo esc_html($metric[3]); ?></small></div>
                    </article>
                <?php endforeach; ?>
            </section>
            <div class="byl-affiliate-panels">
                <section class="byl-affiliate-card byl-affiliate-performance" id="performance" aria-labelledby="byl-performance-title">
                    <div class="byl-affiliate-card-top"><h2 id="byl-performance-title">Recent Performance</h2><span class="byl-affiliate-tag">Latest referrals</span></div>
                    <?php
                    $bars = array_reverse(array_slice($referrals, 0, 10));
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
                        <div class="byl-affiliate-chart" role="img" aria-label="Recent referral commissions: <?php echo esc_attr((string) count($bars)); ?> records">
                            <?php foreach ($bars as $entry):
                                $amount = is_numeric($entry->amount ?? null) ? max(0.0, (float) $entry->amount) : 0.0;
                                $height = round(max(4, 100 * $amount / $max));
                            ?>
                                <div class="byl-affiliate-bar" style="--byl-bar-height:<?php echo esc_attr((string) $height); ?>%" title="<?php echo esc_attr(byl_v3_affiliate_money($amount, (string) ($entry->currency ?? $currency))); ?>"><span></span></div>
                            <?php endforeach; ?>
                        </div>
                        <p class="byl-affiliate-chart-caption">Each bar represents one recent referral; values and statuses come from AffiliateWP.</p>
                    <?php else : ?>
                        <div class="byl-affiliate-empty-chart"><span aria-hidden="true">↗</span><p><?php echo $chart_comparable ? 'More referral history is needed for a meaningful trend chart.' : 'Multiple currencies cannot be compared on one chart.'; ?></p><small>Only comparable AffiliateWP amounts are plotted.</small></div>
                    <?php endif; ?>
                </section>
                <section class="byl-affiliate-card byl-affiliate-sharing" id="sharing" aria-labelledby="byl-sharing-title">
                    <div class="byl-affiliate-sharing-top"><p class="byl-affiliate-eyebrow">YOUR PERSONAL LINK</p><h2 id="byl-sharing-title">Share & earn</h2><p>Your unique referral URL is supplied by AffiliateWP.</p></div>
                    <?php if ($referral_url): ?>
                        <div class="byl-affiliate-link-wrap">
                            <label for="byl-affiliate-link">Your referral link</label>
                            <div class="byl-affiliate-copy-row"><input id="byl-affiliate-link" type="text" readonly value="<?php echo esc_attr($referral_url); ?>"><button type="button" class="byl-affiliate-copy" data-byl-copy="#byl-affiliate-link">Copy Link</button></div>
                            <a class="byl-affiliate-open-link" href="<?php echo esc_url($referral_url); ?>" rel="nofollow">Open referral URL <span aria-hidden="true">↗</span></a>
                        </div>
                    <?php else : ?>
                        <div class="byl-affiliate-link-missing">Your verified referral link is currently unavailable. Please contact your affiliate manager.</div>
                    <?php endif; ?>
                    <?php
                    $coupon_html = shortcode_exists('affiliate_coupons') ? trim((string) do_shortcode('[affiliate_coupons]')) : '';
                    ?>
                    <?php if ($coupon_html !== ''): ?>
                        <div class="byl-affiliate-assigned-coupons"><h3>Your assigned promo codes</h3><?php echo wp_kses_post($coupon_html); ?></div>
                    <?php else: ?>
                        <p class="byl-affiliate-coupon-note">Your verified promotional codes will appear here when assigned through AffiliateWP. No sample code is shown.</p>
                    <?php endif; ?>
                </section>
                <section class="byl-affiliate-card byl-affiliate-referrals" id="referrals" aria-labelledby="byl-referrals-title">
                    <div class="byl-affiliate-card-top"><h2 id="byl-referrals-title">Recent Referral Orders</h2><span class="byl-affiliate-tag">Latest <?php echo esc_html((string) count($referrals)); ?></span></div>
                    <?php if ($referrals): ?>
                        <div class="byl-affiliate-table-wrap"><table class="byl-affiliate-table"><thead><tr><th scope="col">Referral</th><th scope="col">Date</th><th scope="col">Commission</th><th scope="col">Status</th></tr></thead><tbody>
                        <?php foreach ($referrals as $entry):
                            $date = !empty($entry->date) ? mysql2date('M j, Y', (string) $entry->date) : '—';
                            $status = strtolower((string) ($entry->status ?? 'pending'));
                            $status = in_array($status, ['pending','unpaid','paid','rejected'], true) ? $status : 'unknown';
                        ?>
                            <tr><td>#<?php echo esc_html((string) ($entry->referral_id ?? '—')); ?></td><td><?php echo esc_html($date); ?></td><td><?php echo esc_html(byl_v3_affiliate_money($entry->amount ?? null, (string) ($entry->currency ?? $currency))); ?></td><td><span class="byl-affiliate-status byl-affiliate-status--<?php echo esc_attr($status); ?>"><?php echo esc_html(ucfirst($status)); ?></span></td></tr>
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
                    <div class="byl-affiliate-card-top"><h2 id="byl-payouts-title">Payouts</h2><span class="byl-affiliate-tag">From AffiliateWP</span></div>
                    <div class="byl-affiliate-payout-summary"><span aria-hidden="true">◉</span><div><small>Current unpaid balance</small><strong><?php echo esc_html(byl_v3_affiliate_metric($account->unpaid_earnings ?? null, true, $currency)); ?></strong></div></div>
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
                    <div class="byl-affiliate-card-top"><h2 id="byl-creatives-title">Creatives & Assets</h2><span class="byl-affiliate-tag">Approved assets only</span></div>
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
                    <?php else: ?><div class="byl-affiliate-creative-empty"><span aria-hidden="true">◇</span><p>Your affiliate team has not yet published creatives here.</p><small>Only approved promotional material will be displayed.</small></div><?php endif; ?>
                </section>
                <section class="byl-affiliate-card byl-affiliate-support" id="account" aria-labelledby="byl-account-title">
                    <p class="byl-affiliate-eyebrow">WE'RE HERE TO HELP</p><h2 id="byl-account-title">Account & Support</h2>
                    <p>Manage your shopping profile, reach the team about a commission or update payout details through the program's approved process.</p>
                    <div class="byl-affiliate-support-links">
                        <a href="<?php echo esc_url(byl_v3_account_url()); ?>">My Shopping Account <span aria-hidden="true">↗</span></a>
                        <a href="<?php echo esc_url(home_url('/affiliate-area/')); ?>">Existing AffiliateWP settings &amp; payout preferences <span aria-hidden="true">↗</span></a>
                        <a href="<?php echo esc_url(home_url('/customer-help/')); ?>">Contact Support <span aria-hidden="true">↗</span></a>
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
