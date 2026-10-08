<?php
/** Checkout-only trust/legal footer: no marketing/navigation funnels. */
declare(strict_types=1);
defined('ABSPATH') || exit;
$privacy_url = function_exists('get_privacy_policy_url') ? (string) get_privacy_policy_url() : '';
if ($privacy_url === '') { $privacy_url = home_url('/privacy-policies/'); }
$terms_page_id = function_exists('wc_get_page_id') ? (int) wc_get_page_id('terms') : 0;
$terms_url = $terms_page_id > 0 && function_exists('wc_get_page_permalink') ? (string) wc_get_page_permalink('terms') : '';
if ($terms_url === '') { $terms_url = home_url('/terms-and-conditions/'); }
?>
<footer class="byl-secure-footer" role="contentinfo">
  <div class="byl-secure-footer__inner">
    <div class="byl-secure-footer__trust">
      <span><?php esc_html_e('Discreet packaging', 'beyourlover-v3'); ?></span>
      <span><?php esc_html_e('Shipping support', 'beyourlover-v3'); ?></span>
      <span><?php esc_html_e('Privacy matters', 'beyourlover-v3'); ?></span>
    </div>
    <nav class="byl-secure-footer__legal" aria-label="<?php esc_attr_e('Checkout policies', 'beyourlover-v3'); ?>">
      <a href="<?php echo esc_url($privacy_url); ?>"><?php esc_html_e('Privacy', 'beyourlover-v3'); ?></a>
      <a href="<?php echo esc_url($terms_url); ?>"><?php esc_html_e('Terms', 'beyourlover-v3'); ?></a>
      <a href="<?php echo esc_url(home_url('/shipping-policies/')); ?>"><?php esc_html_e('Shipping', 'beyourlover-v3'); ?></a>
      <a href="<?php echo esc_url(home_url('/customer-help/')); ?>"><?php esc_html_e('Support', 'beyourlover-v3'); ?></a>
    </nav>
    <small>© <?php echo esc_html(wp_date('Y')); ?> BeYourLover</small>
  </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
