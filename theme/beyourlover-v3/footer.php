<?php
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }
?>
<footer class="byl-shell-footer">
  <div class="byl-container byl-footer-main">
    <div class="byl-footer-brand">
      <a href="<?php echo esc_url(home_url('/')); ?>" aria-label="BeYourLover home"><?php echo byl_v3_brand_logo(); ?></a>
      <p>Designed for desire. Built for clarity.</p>
    </div>
    <nav class="byl-footer-grid" aria-label="Footer navigation">
      <div>
        <h2>Shop</h2>
        <a href="<?php echo esc_url(home_url('/shop/')); ?>">All Products</a>
        <a href="<?php echo esc_url(home_url('/new-arrivals/')); ?>">New Arrivals</a>
        <a href="<?php echo esc_url(home_url('/best-sellers/')); ?>">Best Sellers</a>
      </div>
      <div>
        <h2>Explore</h2>
        <a href="<?php echo esc_url(home_url('/app-guide/')); ?>">App</a>
        <a href="<?php echo esc_url(home_url('/blog/')); ?>">Learn</a>
        <a href="<?php echo esc_url(home_url('/docs/')); ?>">Guides & FAQ</a>
      </div>
      <div>
        <h2>Support</h2>
        <a href="<?php echo esc_url(home_url('/customer-help/')); ?>">Customer Help</a>
        <a href="<?php echo esc_url(byl_v3_account_url()); ?>">My Account</a>
        <a href="<?php echo esc_url(byl_v3_cart_url()); ?>">Cart</a>
      </div>
    </nav>
  </div>
  <div class="byl-container byl-footer-bottom">
    <p>© <?php echo esc_html(wp_date('Y')); ?> BeYourLover. All rights reserved.</p>
    <p>Discreet by design.</p>
  </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
