<?php
/** Cart-only compact trust/legal footer. */
declare(strict_types=1);
defined('ABSPATH') || exit;
?>
<footer class="byl-focus-footer">
  <div class="byl-focus-footer__inner">
    <div class="byl-focus-trust" aria-label="<?php esc_attr_e('Shopping support', 'beyourlover-v3'); ?>">
      <span><strong><?php esc_html_e('Discreet packaging', 'beyourlover-v3'); ?></strong><small><?php esc_html_e('Privacy-minded delivery', 'beyourlover-v3'); ?></small></span>
      <span><strong><?php esc_html_e('Shipping information', 'beyourlover-v3'); ?></strong><small><a href="<?php echo esc_url(home_url('/shipping/')); ?>"><?php esc_html_e('View delivery details', 'beyourlover-v3'); ?></a></small></span>
      <span><strong><?php esc_html_e('Customer support', 'beyourlover-v3'); ?></strong><small><a href="<?php echo esc_url(home_url('/customer-help/')); ?>"><?php esc_html_e('Get help', 'beyourlover-v3'); ?></a></small></span>
    </div>
    <nav class="byl-focus-legal" aria-label="<?php esc_attr_e('Policies', 'beyourlover-v3'); ?>">
      <a href="<?php echo esc_url(home_url('/privacy-policy/')); ?>"><?php esc_html_e('Privacy', 'beyourlover-v3'); ?></a>
      <a href="<?php echo esc_url(home_url('/terms-and-conditions/')); ?>"><?php esc_html_e('Terms', 'beyourlover-v3'); ?></a>
      <a href="<?php echo esc_url(home_url('/returns/')); ?>"><?php esc_html_e('Returns', 'beyourlover-v3'); ?></a>
      <a href="<?php echo esc_url(home_url('/customer-help/')); ?>"><?php esc_html_e('Support', 'beyourlover-v3'); ?></a>
    </nav>
    <small class="byl-focus-copy">© <?php echo esc_html(wp_date('Y')); ?> BeYourLover.</small>
  </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
