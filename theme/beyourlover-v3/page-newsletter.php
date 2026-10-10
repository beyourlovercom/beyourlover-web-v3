<?php
/**
 * BYL V3 Newsletter presentation only. Andy Core owns the form, consent and API.
 * No stored post_content is echoed: the historical newsletter Page embeds an
 * obsolete WPForms placeholder and must not produce two forms.
 */
declare(strict_types=1);
defined('ABSPATH') || exit;

// Respect historical custom template selection. Do not change other Pages.
$byl_newsletter_slug = function_exists('get_page_template_slug')
    ? (string) get_page_template_slug(get_queried_object_id()) : '';
if ($byl_newsletter_slug !== '' && $byl_newsletter_slug !== 'default') {
    require get_stylesheet_directory() . '/index.php';
    return;
}

$byl_newsletter_css = '/assets/css/newsletter.css';
$byl_newsletter_css_path = get_stylesheet_directory() . $byl_newsletter_css;
wp_enqueue_style(
    'byl-v3-newsletter',
    get_stylesheet_directory_uri() . $byl_newsletter_css,
    ['byl-v3-motion'],
    is_file($byl_newsletter_css_path) ? (string) filemtime($byl_newsletter_css_path) : (string) wp_get_theme()->get('Version')
);

// Only the M1 Core controller is a valid integration, not the old inert shortcode.
// The legacy shortcode is not a working signup; never show a submit button
// while Core API or outbound email remains OFF.
$byl_newsletter_core_ready = class_exists('YBY_Newsletter_REST_Controller')
    && shortcode_exists('yby_subscribe')
    && is_callable(['YBY_Newsletter_REST_Controller', 'enabled'])
    && is_callable(['YBY_Newsletter_REST_Controller', 'outbound_enabled'])
    && YBY_Newsletter_REST_Controller::enabled()
    && YBY_Newsletter_REST_Controller::outbound_enabled();
$byl_newsletter_privacy = '';
$byl_newsletter_policy_page = get_page_by_path('privacy-policies', OBJECT, 'page');
if ($byl_newsletter_policy_page instanceof WP_Post && $byl_newsletter_policy_page->post_status === 'publish') {
    $byl_newsletter_privacy = get_permalink($byl_newsletter_policy_page);
} elseif (function_exists('get_privacy_policy_url')) {
    $byl_newsletter_privacy = (string) get_privacy_policy_url();
}

get_header();
?>
<main id="main-content" class="byl-newsletter" data-byl-newsletter-page="v1">
  <div class="byl-newsletter__wrap">
    <div class="byl-newsletter__breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'beyourlover-v3'); ?></a><span aria-hidden="true">/</span><span><?php esc_html_e('Newsletter', 'beyourlover-v3'); ?></span></div>
    <section class="byl-newsletter__hero" aria-labelledby="byl-newsletter-heading">
      <p class="byl-newsletter__eyebrow"><?php esc_html_e('THE BYL EDIT', 'beyourlover-v3'); ?></p>
      <h1 id="byl-newsletter-heading"><?php esc_html_e('Stay in the loop.', 'beyourlover-v3'); ?></h1>
      <p class="byl-newsletter__lede"><?php esc_html_e('Discover new arrivals, helpful guides and updates from BeYourLover. Choose whether you want to receive our emails.', 'beyourlover-v3'); ?></p>
      <div class="byl-newsletter__panel" aria-labelledby="byl-newsletter-form-heading">
        <h2 id="byl-newsletter-form-heading"><?php esc_html_e('Join our newsletter', 'beyourlover-v3'); ?></h2>
        <?php if ($byl_newsletter_core_ready && $byl_newsletter_privacy !== '') : ?>
          <div class="byl-newsletter__core">
            <?php
            // Deliberately render Core's own controlled UI just once. Its
            // enabled/disabled state, consent and responses remain Core-owned.
            echo do_shortcode('[yby_subscribe]');
            ?>
          </div>
        <?php else : ?>
          <p class="byl-newsletter__unavailable" role="status"><?php esc_html_e('Newsletter sign-up is not available yet. Please check back later.', 'beyourlover-v3'); ?></p>
        <?php endif; ?>
        <p class="byl-newsletter__fine-print"><?php esc_html_e('Email updates are optional. You can unsubscribe using the link in an email at any time.', 'beyourlover-v3'); ?>
        <?php if ($byl_newsletter_privacy !== '') : ?>
          <a href="<?php echo esc_url($byl_newsletter_privacy); ?>"><?php esc_html_e('Privacy Policy', 'beyourlover-v3'); ?></a>
        <?php endif; ?>
        </p>
      </div>
    </section>
    <section class="byl-newsletter__details" aria-labelledby="byl-newsletter-details-heading">
      <h2 id="byl-newsletter-details-heading"><?php esc_html_e('What to expect', 'beyourlover-v3'); ?></h2>
      <div class="byl-newsletter__features">
        <article><span class="byl-newsletter__number" aria-hidden="true">01</span><h3><?php esc_html_e('New arrivals', 'beyourlover-v3'); ?></h3><p><?php esc_html_e('Explore additions to our collection.', 'beyourlover-v3'); ?></p></article>
        <article><span class="byl-newsletter__number" aria-hidden="true">02</span><h3><?php esc_html_e('Useful reading', 'beyourlover-v3'); ?></h3><p><?php esc_html_e('Find thoughtful articles and product guides.', 'beyourlover-v3'); ?></p></article>
        <article><span class="byl-newsletter__number" aria-hidden="true">03</span><h3><?php esc_html_e('Brand updates', 'beyourlover-v3'); ?></h3><p><?php esc_html_e('Hear about relevant news and announcements.', 'beyourlover-v3'); ?></p></article>
      </div>
    </section>
    <section class="byl-newsletter__process" aria-labelledby="byl-newsletter-process-heading">
      <h2 id="byl-newsletter-process-heading"><?php esc_html_e('How subscribing works', 'beyourlover-v3'); ?></h2>
      <ol><li><?php esc_html_e('Enter your email and explicitly agree to receive marketing messages.', 'beyourlover-v3'); ?></li><li><?php esc_html_e('When sign-up becomes available, follow the instructions in your confirmation email.', 'beyourlover-v3'); ?></li><li><?php esc_html_e('Only confirming your address completes the subscription.', 'beyourlover-v3'); ?></li></ol>
    </section>
  </div>
</main>
<?php get_footer(); 
