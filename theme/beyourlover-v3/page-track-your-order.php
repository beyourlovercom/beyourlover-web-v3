<?php
/**
 * BYL V3 — dedicated Track Your Order page (page slug: track-your-order).
 * Presentation only; WooCommerce owns lookup validation, order authorization and status output.
 * The historic page content contains global legacy styles, so it must not be rendered here.
 */
declare(strict_types=1);
defined('ABSPATH') || exit;

get_header();

$byl_account_url = function_exists('byl_v3_account_url')
    ? byl_v3_account_url() : home_url('/my-account/');
$byl_support_url = home_url('/customer-help/');
?>
<main id="main-content" class="byl-track-page">
  <div class="byl-track-shell">
    <section class="byl-track-hero" aria-labelledby="byl-track-title">
      <div class="byl-track-hero-copy">
        <p class="byl-track-kicker"><?php esc_html_e('Order tracking', 'beyourlover-v3'); ?></p>
        <h1 id="byl-track-title"><?php esc_html_e('Track Your Order', 'beyourlover-v3'); ?></h1>
        <p class="byl-track-intro"><?php esc_html_e('Enter your order details to check your order status and available shipping updates.', 'beyourlover-v3'); ?></p>
        <ul class="byl-track-benefits" aria-label="<?php esc_attr_e('Order tracking benefits', 'beyourlover-v3'); ?>">
          <li><span class="byl-track-benefit-icon" aria-hidden="true">◎</span><span><strong><?php esc_html_e('Order status', 'beyourlover-v3'); ?></strong><small><?php esc_html_e('View the latest available update', 'beyourlover-v3'); ?></small></span></li>
          <li><span class="byl-track-benefit-icon" aria-hidden="true">✉</span><span><strong><?php esc_html_e('Track with ease', 'beyourlover-v3'); ?></strong><small><?php esc_html_e('Use your order number and email', 'beyourlover-v3'); ?></small></span></li>
          <li><span class="byl-track-benefit-icon" aria-hidden="true">♡</span><span><strong><?php esc_html_e('Here to help', 'beyourlover-v3'); ?></strong><small><?php esc_html_e('Support when you need it', 'beyourlover-v3'); ?></small></span></li>
        </ul>
      </div>
      <svg class="byl-track-illustration" viewBox="0 0 370 225" aria-hidden="true" focusable="false">
        <defs>
          <linearGradient id="byl-track-box-front" x1="0" x2="1" y1="0" y2="1"><stop stop-color="#f0bbc4"/><stop offset="1" stop-color="#d98c9d"/></linearGradient>
          <linearGradient id="byl-track-box-side" x1="0" x2="1" y1="0" y2="1"><stop stop-color="#e9a4b3"/><stop offset="1" stop-color="#c8798e"/></linearGradient>
          <linearGradient id="byl-track-box-top" x1="0" x2="1" y1="0" y2="1"><stop stop-color="#ffe7e9"/><stop offset="1" stop-color="#efbec8"/></linearGradient>
        </defs>
        <ellipse cx="158" cy="206" rx="110" ry="15" fill="#d58a9d" opacity=".12"/>
        <path d="M245 154 C284 188 325 163 323 121 C321 100 344 94 359 96" fill="none" stroke="#bf617b" stroke-width="2.5" stroke-dasharray="6 8" stroke-linecap="round"/>
        <path d="M336 60 C316 60 309 74 309 87 C309 106 336 127 336 127 C336 127 364 106 364 87 C364 74 354 60 336 60Z" fill="#b95570"/>
        <circle cx="336" cy="85" r="9" fill="#fff8f8"/>
        <path d="M77 99 L175 67 L262 100 L163 134Z" fill="url(#byl-track-box-top)" stroke="#dca4af"/>
        <path d="M77 99 L163 134 L163 213 L77 175Z" fill="url(#byl-track-box-front)" stroke="#d99baa"/>
        <path d="M163 134 L262 100 L262 177 L163 213Z" fill="url(#byl-track-box-side)" stroke="#c77c91"/>
        <path d="M119 85 L210 116 L210 127 L119 98Z" fill="#fff4f3" opacity=".88"/>
        <path d="M163 134 L163 213" stroke="#bb788c" stroke-width="2" opacity=".5"/>
        <rect x="179" y="148" width="41" height="44" rx="5" fill="#a84b68" opacity=".95"/>
        <path d="M188 174 l8 -11 9 11 -9 9Z" fill="#ffe7eb"/>
        <path d="M197 169 v13" stroke="#a84b68" stroke-width="1.5"/>
      </svg>
    </section>

    <section class="byl-track-panel byl-track-query" aria-labelledby="byl-track-query-title">
      <h2 id="byl-track-query-title"><?php esc_html_e('Find Your Order', 'beyourlover-v3'); ?></h2>
      <?php if (shortcode_exists('woocommerce_order_tracking')) : ?>
        <?php echo do_shortcode('[woocommerce_order_tracking]'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
      <?php else : ?>
        <p class="byl-track-unavailable"><?php esc_html_e('Order lookup is temporarily unavailable. Please contact our support team.', 'beyourlover-v3'); ?></p>
      <?php endif; ?>
      <p class="byl-track-hint"><span class="byl-track-hint-icon" aria-hidden="true">i</span>
        <?php esc_html_e("Can't find your order number? Check your confirmation email or", 'beyourlover-v3'); ?>
        <a href="<?php echo esc_url($byl_account_url); ?>"><?php esc_html_e('sign in to your account', 'beyourlover-v3'); ?></a>.
      </p>
    </section>

    <section class="byl-track-panel byl-track-support" aria-labelledby="byl-track-support-title">
      <h2 id="byl-track-support-title"><?php esc_html_e('Shipping & Order Support', 'beyourlover-v3'); ?></h2>
      <p class="byl-track-support-lead"><?php esc_html_e('Find quick answers to common questions about tracking, shipping, and your order.', 'beyourlover-v3'); ?></p>
      <div class="byl-track-faq-list">
        <details class="byl-track-faq">
          <summary><span class="byl-track-faq-icon" aria-hidden="true">▣</span><span><?php esc_html_e('What is a tracking number?', 'beyourlover-v3'); ?></span><span class="byl-track-chevron" aria-hidden="true"></span></summary>
          <div class="byl-track-answer"><p><?php esc_html_e('A tracking number is a unique reference assigned by the delivery carrier. It is different from your order number and is normally available once your parcel has shipped.', 'beyourlover-v3'); ?></p></div>
        </details>
        <details class="byl-track-faq">
          <summary><span class="byl-track-faq-icon" aria-hidden="true">⌕</span><span><?php esc_html_e('How do I find my tracking number?', 'beyourlover-v3'); ?></span><span class="byl-track-chevron" aria-hidden="true"></span></summary>
          <div class="byl-track-answer"><p><?php esc_html_e('Check your shipping confirmation email or your order details when shipment information has been made available. A tracking number may not be assigned until your order is dispatched.', 'beyourlover-v3'); ?></p></div>
        </details>
        <details class="byl-track-faq">
          <summary><span class="byl-track-faq-icon" aria-hidden="true">≡</span><span><?php esc_html_e('How do I find my order details?', 'beyourlover-v3'); ?></span><span class="byl-track-chevron" aria-hidden="true"></span></summary>
          <div class="byl-track-answer"><p><?php esc_html_e('You can look up an order using the order number and billing email above. If you purchased using an account, you can also view available order details in', 'beyourlover-v3'); ?> <a href="<?php echo esc_url($byl_account_url); ?>"><?php esc_html_e('My Account', 'beyourlover-v3'); ?></a>.</p></div>
        </details>
        <details class="byl-track-faq">
          <summary><span class="byl-track-faq-icon" aria-hidden="true">◇</span><span><?php esc_html_e("I haven't received my order", 'beyourlover-v3'); ?></span><span class="byl-track-chevron" aria-hidden="true"></span></summary>
          <div class="byl-track-answer"><p><?php esc_html_e('First check the current order status above. If your delivery seems delayed or the available information does not explain the issue,', 'beyourlover-v3'); ?> <a href="<?php echo esc_url($byl_support_url); ?>"><?php esc_html_e('contact our support team', 'beyourlover-v3'); ?></a> <?php esc_html_e('with your order number. Do not share payment card details.', 'beyourlover-v3'); ?></p></div>
        </details>
        <details class="byl-track-faq">
          <summary><span class="byl-track-faq-icon" aria-hidden="true">✉</span><span><?php esc_html_e("Why haven't I received order emails?", 'beyourlover-v3'); ?></span><span class="byl-track-chevron" aria-hidden="true"></span></summary>
          <div class="byl-track-answer"><p><?php esc_html_e('Check spam or junk folders and verify the email address used at checkout. If you still need help,', 'beyourlover-v3'); ?> <a href="<?php echo esc_url($byl_support_url); ?>"><?php esc_html_e('contact support', 'beyourlover-v3'); ?></a>.</p></div>
        </details>
      </div>
    </section>
  </div>
</main>
<?php get_footer(); ?>
