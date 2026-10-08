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
// Legacy live help documents verified accessible; not yet present on Dev (404).
// Re-check these routes during the separately gated Dev → WWW cutover.
$byl_track_guide = 'https://www.beyourlover.com/docs/shipping/how-to-track-my-order/';
$byl_missing_order_guide = 'https://www.beyourlover.com/docs/shipping/i-havent-received-my-order/';
$byl_email_guide = 'https://www.beyourlover.com/docs/shipping/havent-received-order-emails/';
$byl_shipping_email_guide = 'https://www.beyourlover.com/docs/shipping/shipping-info-noticement/';
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
          <summary><span class="byl-track-faq-icon" aria-hidden="true">▣</span><span><?php esc_html_e('What Is a Tracking Number?', 'beyourlover-v3'); ?></span><span class="byl-track-chevron" aria-hidden="true"></span></summary>
          <div class="byl-track-answer">
            <p><?php esc_html_e('Your tracking number is issued by the shipping carrier, usually after dispatch. It is different from your BeYourLover order number. Some common formats include:', 'beyourlover-v3'); ?></p>
            <ul class="byl-track-carriers" aria-label="<?php esc_attr_e('Common tracking number examples', 'beyourlover-v3'); ?>">
              <li><strong>UPS</strong><span><?php esc_html_e('Often starts with 1Z', 'beyourlover-v3'); ?></span></li>
              <li><strong>FedEx</strong><span><?php esc_html_e('Commonly 12 digits; other formats exist', 'beyourlover-v3'); ?></span></li>
              <li><strong>USPS</strong><span><?php esc_html_e('Often 20–22 digits for domestic parcels', 'beyourlover-v3'); ?></span></li>
              <li><strong>DHL</strong><span><?php esc_html_e('Formats vary by service and region', 'beyourlover-v3'); ?></span></li>
            </ul>
            <p class="byl-track-tip"><?php esc_html_e('Tip: Copy the full number from your shipping confirmation to avoid confusing 0/O or 1/I. Carrier formats are examples, not a validation rule.', 'beyourlover-v3'); ?></p>
          </div>
        </details>
        <details class="byl-track-faq">
          <summary><span class="byl-track-faq-icon" aria-hidden="true">⌕</span><span><?php esc_html_e('How to Find Your Tracking Number?', 'beyourlover-v3'); ?></span><span class="byl-track-chevron" aria-hidden="true"></span></summary>
          <div class="byl-track-answer">
            <ol class="byl-track-steps">
              <li><strong><?php esc_html_e('Check your shipping confirmation email', 'beyourlover-v3'); ?></strong><span><?php esc_html_e('Look for a dispatch update sent to the email used at checkout.', 'beyourlover-v3'); ?></span></li>
              <li><strong><?php esc_html_e('Visit your order details', 'beyourlover-v3'); ?></strong><span><?php esc_html_e('Sign in to My Account if you used an account when placing the order.', 'beyourlover-v3'); ?></span></li>
              <li><strong><?php esc_html_e('Check the shipping label if available', 'beyourlover-v3'); ?></strong><span><?php esc_html_e('A tracking reference may be printed on the parcel label.', 'beyourlover-v3'); ?></span></li>
              <li><strong><?php esc_html_e('Ask our support team', 'beyourlover-v3'); ?></strong><span><?php esc_html_e('If your parcel has shipped but tracking is missing, contact us with your order number.', 'beyourlover-v3'); ?></span></li>
            </ol>
            <p><a href="<?php echo esc_url($byl_account_url); ?>"><?php esc_html_e('Open My Account', 'beyourlover-v3'); ?> →</a></p>
          </div>
        </details>
        <details class="byl-track-faq">
          <summary><span class="byl-track-faq-icon" aria-hidden="true">≡</span><span><?php esc_html_e('How to Find My Order Details?', 'beyourlover-v3'); ?></span><span class="byl-track-chevron" aria-hidden="true"></span></summary>
          <div class="byl-track-answer">
            <p><?php esc_html_e('Use your order number and billing email in the form above to check its status. If you ordered through an account, your order history and available details are in My Account.', 'beyourlover-v3'); ?></p>
            <a class="byl-track-inline-link" href="<?php echo esc_url($byl_account_url); ?>"><?php esc_html_e('Sign in to My Account', 'beyourlover-v3'); ?> →</a>
          </div>
        </details>
        <details class="byl-track-faq">
          <summary><span class="byl-track-faq-icon" aria-hidden="true">◇</span><span><?php esc_html_e("I Haven't Received My Order", 'beyourlover-v3'); ?></span><span class="byl-track-chevron" aria-hidden="true"></span></summary>
          <div class="byl-track-answer">
            <p><?php esc_html_e('We can help you investigate a delayed delivery. First check the order status and any available carrier update. If the estimated delivery window has passed, review our guide or contact support.', 'beyourlover-v3'); ?></p>
            <p><a class="byl-track-inline-link" href="<?php echo esc_url($byl_missing_order_guide); ?>"><?php esc_html_e('Read the missing-order guide', 'beyourlover-v3'); ?> →</a></p>
            <p><?php esc_html_e('Please include your order number when contacting us. Do not send card details or photos of identity documents.', 'beyourlover-v3'); ?></p>
          </div>
        </details>
        <details class="byl-track-faq">
          <summary><span class="byl-track-faq-icon" aria-hidden="true">✉</span><span><?php esc_html_e("Why Haven't I Received Order Emails?", 'beyourlover-v3'); ?></span><span class="byl-track-chevron" aria-hidden="true"></span></summary>
          <div class="byl-track-answer">
            <p><?php esc_html_e('Try these quick checks:', 'beyourlover-v3'); ?></p>
            <ul class="byl-track-checks">
              <li><?php esc_html_e('Look in spam, junk, promotions, and other filtered folders.', 'beyourlover-v3'); ?></li>
              <li><?php esc_html_e('Verify the email address entered at checkout.', 'beyourlover-v3'); ?></li>
              <li><?php esc_html_e('Allow mail from support@beyourlover.com when appropriate.', 'beyourlover-v3'); ?></li>
            </ul>
            <p><a class="byl-track-inline-link" href="<?php echo esc_url($byl_email_guide); ?>"><?php esc_html_e('See the email troubleshooting guide', 'beyourlover-v3'); ?> →</a></p>
          </div>
        </details>
        <details class="byl-track-faq">
          <summary><span class="byl-track-faq-icon" aria-hidden="true">✦</span><span><?php esc_html_e('How Will the Shipping Info Be Sent via Email?', 'beyourlover-v3'); ?></span><span class="byl-track-chevron" aria-hidden="true"></span></summary>
          <div class="byl-track-answer">
            <p><?php esc_html_e('Depending on the order, carrier, and notification settings, you may receive updates at these stages:', 'beyourlover-v3'); ?></p>
            <ol class="byl-track-timeline">
              <li><strong><?php esc_html_e('Order confirmed', 'beyourlover-v3'); ?></strong><span><?php esc_html_e('Confirmation details once the order is accepted.', 'beyourlover-v3'); ?></span></li>
              <li><strong><?php esc_html_e('Order shipped', 'beyourlover-v3'); ?></strong><span><?php esc_html_e('Carrier and tracking reference when available.', 'beyourlover-v3'); ?></span></li>
              <li><strong><?php esc_html_e('In transit / out for delivery', 'beyourlover-v3'); ?></strong><span><?php esc_html_e('Carrier progress updates where supported.', 'beyourlover-v3'); ?></span></li>
              <li><strong><?php esc_html_e('Delivered', 'beyourlover-v3'); ?></strong><span><?php esc_html_e('Delivery confirmation if provided by the carrier.', 'beyourlover-v3'); ?></span></li>
            </ol>
            <p class="byl-track-tip"><?php esc_html_e('The availability and timing of shipping emails vary. These stages are an example, not a guarantee of automatic notifications.', 'beyourlover-v3'); ?></p>
            <a class="byl-track-inline-link" href="<?php echo esc_url($byl_shipping_email_guide); ?>"><?php esc_html_e('Learn more about shipping notifications', 'beyourlover-v3'); ?> →</a>
          </div>
        </details>
      </div>
    </section>

    <section class="byl-track-assist" aria-labelledby="byl-track-assist-title">
      <div class="byl-track-assist-main">
        <p class="byl-track-kicker"><?php esc_html_e('Still need help?', 'beyourlover-v3'); ?></p>
        <h2 id="byl-track-assist-title"><?php esc_html_e("We're here to help you find your order.", 'beyourlover-v3'); ?></h2>
        <p><?php esc_html_e('If the order lookup did not answer your question, start with our step-by-step tracking guide or contact our support team.', 'beyourlover-v3'); ?></p>
        <div class="byl-track-assist-actions">
          <a class="byl-track-guide-button" href="<?php echo esc_url($byl_track_guide); ?>"><?php esc_html_e('View Tracking Guide', 'beyourlover-v3'); ?> <span aria-hidden="true">→</span></a>
          <a class="byl-track-assist-email" href="mailto:support@beyourlover.com">support@beyourlover.com</a>
        </div>
        <p class="byl-track-assist-thanks"><?php esc_html_e('Thank you for your trust and love!', 'beyourlover-v3'); ?></p>
      </div>
      <div class="byl-track-assist-aside" aria-hidden="true">
        <span class="byl-track-assist-symbol">♡</span>
        <span class="byl-track-assist-aside-copy"><?php esc_html_e('Need a hand?', 'beyourlover-v3'); ?></span>
      </div>
    </section>

    <section class="byl-track-reassurance" aria-label="<?php esc_attr_e('Order support highlights', 'beyourlover-v3'); ?>">
      <div><span class="byl-track-reassure-icon" aria-hidden="true">✓</span><span><strong><?php esc_html_e('Secure order lookup', 'beyourlover-v3'); ?></strong><small><?php esc_html_e('Order number and billing email required', 'beyourlover-v3'); ?></small></span></div>
      <div><span class="byl-track-reassure-icon" aria-hidden="true">◈</span><span><strong><?php esc_html_e('Discreet shipping', 'beyourlover-v3'); ?></strong><small><?php esc_html_e('Delivery details depend on your order', 'beyourlover-v3'); ?></small></span></div>
      <div><span class="byl-track-reassure-icon" aria-hidden="true">✉</span><span><strong><?php esc_html_e('Here to support you', 'beyourlover-v3'); ?></strong><small><?php esc_html_e('Questions about your order? Get in touch', 'beyourlover-v3'); ?></small></span></div>
    </section>
  </div>
</main>
<?php get_footer(); ?>
