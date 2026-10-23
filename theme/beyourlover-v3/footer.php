<?php
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }
$footer_profile = byl_v3_current_footer_profile();
$footer_groups = byl_v3_footer_groups($footer_profile);
?>
<footer class="byl-shell-footer byl-shell-footer--<?php echo esc_attr($footer_profile); ?>" data-byl-footer-profile="<?php echo esc_attr($footer_profile); ?>">
  <div class="byl-container byl-footer-main">
    <div class="byl-footer-brand">
      <a href="<?php echo esc_url(home_url('/')); ?>" aria-label="BeYourLover home"><?php echo byl_v3_brand_logo(); ?></a>
      <?php if ($footer_profile === 'commerce'): ?>
        <p>Shopping made clear, discreet and easy to support.</p>
      <?php elseif ($footer_profile === 'editorial'): ?>
        <p>Guides, stories and thoughtful discovery from BeYourLover.</p>
      <?php elseif ($footer_profile === 'landing'): ?>
        <p>Discreet by design.</p>
      <?php elseif ($footer_profile === 'utility'): ?>
        <p>Help when you need it.</p>
      <?php else: ?>
        <p>Designed for desire. Built for clarity.</p>
      <?php endif; ?>
    </div>
    <nav class="byl-footer-grid" aria-label="Footer navigation">
      <?php foreach ($footer_groups as $group): ?>
        <div class="byl-footer-group">
          <?php if (!empty($group['label'])): ?><h2><?php echo esc_html((string)$group['label']); ?></h2><?php endif; ?>
          <?php foreach (($group['items'] ?? []) as $item): ?>
            <a href="<?php echo esc_url((string)$item['url']); ?>"><?php echo esc_html((string)$item['label']); ?></a>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </nav>
  </div>
  <div class="byl-container byl-footer-bottom">
    <p>© <?php echo esc_html(wp_date('Y')); ?> BeYourLover. All rights reserved.</p>
    <p><?php echo $footer_profile === 'landing' ? 'Secure · Discreet · Supported' : 'Discreet by design.'; ?></p>
    <?php if (shortcode_exists('andy_currency_switcher')): ?>
      <?php $byl_footer_currency = do_shortcode('[andy_currency_switcher style="compact" show_flag="yes" show_symbol="yes" show_code="yes"]'); ?>
      <?php if ($byl_footer_currency !== ''): ?>
        <div class="byl-footer-currency"><?php echo $byl_footer_currency; ?></div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
