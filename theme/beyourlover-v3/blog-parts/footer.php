<?php
/** Standalone Blog V1 footer. WWW Blog modules; no Woo/Shop footer widgets. */
declare(strict_types=1);
defined('ABSPATH') || exit;
?>
<footer class="byl-blogv1-site-footer" aria-label="BeYourLover Blog footer">
  <section class="byl-blogv1-footer-intro">
    <div class="byl-blogv1-container byl-blogv1-footer-intro-inner">
    <div class="byl-blogv1-footer-intro-text" id="byl-blog-footer-about" data-blog-about>
      <h2>Welcome to the BeYourLover Blog</h2>
      <p>The BeYourLover Blog is a place to explore original stories, thoughtful relationship advice,
      intimacy tips and helpful guides. Discover our
      <a href="<?php echo esc_url(home_url('/sex-toy-guides/')); ?>">sex toy guides</a>,
      <a href="<?php echo esc_url(home_url('/sex-tips-advice/')); ?>">tips and advice</a>,
      and <a href="<?php echo esc_url(home_url('/sex-positions/')); ?>">sex positions</a>.</p>
      <div class="byl-blogv1-footer-extra" id="byl-blog-about-extra" hidden>
        <p>Browse our six editorial categories for articles on sexual wellness, relationships, guides, positions and updates.
        Existing WordPress posts, permalinks and categories are retained.</p>
        <p>BeYourLover aims to make personal discovery approachable with practical information and an open, supportive tone.</p>
      </div>
    </div>
    <button type="button" data-blog-about-toggle aria-controls="byl-blog-about-extra" aria-expanded="false">READ MORE <span aria-hidden="true">⌄</span></button>
      </div>
  </section>
  <section class="byl-blogv1-footer-connect">
    <div class="byl-blogv1-container byl-blogv1-footer-connect-inner">
    <div>
      <p class="byl-blogv1-footer-kicker">MORE FROM BEYOURLOVER</p>
      <h2>Stay in the loop.</h2>
      <p>Fresh stories, helpful guides and thoughtful discoveries.</p>
      <a class="byl-blogv1-footer-newsletter" href="<?php echo esc_url(home_url('/newsletter/')); ?>">Explore our newsletter →</a>
    </div>
    <div>
      <h2>Follow us.</h2>
      <nav aria-label="BeYourLover social links" class="byl-blogv1-socials">
        <a href="https://www.facebook.com/beyourlovercom" rel="noopener noreferrer" target="_blank">Facebook</a>
        <a href="https://www.instagram.com/beyourlovertoys/" rel="noopener noreferrer" target="_blank">Instagram</a>
        <a href="https://www.tiktok.com/@beyourlover_planet" rel="noopener noreferrer" target="_blank">TikTok</a>
        <a href="https://www.reddit.com/r/beyourlovercom/" rel="noopener noreferrer" target="_blank">Reddit</a>
      </nav>
    </div>
      </div>
  </section>
  <section class="byl-blogv1-footer-details">
    <div class="byl-blogv1-container byl-blogv1-footer-details-inner">
    <div><h3>BeYourLover</h3><p>Lovely present</p><p>Stories, guides and personal discovery.</p></div>
    <nav aria-label="Blog customer support"><h3>Help</h3>
      <a href="<?php echo esc_url(home_url('/faqs/')); ?>">FAQs</a>
      <a href="<?php echo esc_url(home_url('/shipping-policies/')); ?>">Shipping policies</a>
      <a href="<?php echo esc_url(home_url('/return-policies/')); ?>">Returns</a>
      <a href="<?php echo esc_url(home_url('/track-your-order/')); ?>">Track your order</a>
    </nav>
    <nav aria-label="Blog company links"><h3>Company</h3>
      <a href="<?php echo esc_url(home_url('/about-us/')); ?>">About us</a>
      <a href="<?php echo esc_url(home_url('/contact-us/')); ?>">Contact us</a>
      <a href="<?php echo esc_url(home_url('/affiliates/')); ?>">Affiliates</a>
      <a href="<?php echo esc_url(home_url('/blog/')); ?>">Blog</a>
    </nav>
    <nav aria-label="Blog legal policies"><h3>Policies</h3>
      <a href="<?php echo esc_url(home_url('/privacy-policies/')); ?>">Privacy Policy</a>
      <a href="<?php echo esc_url(home_url('/terms-and-conditions/')); ?>">Terms</a>
    </nav>
      </div>
  </section>
  <div class="byl-blogv1-footer-bottom">
    <div class="byl-blogv1-container byl-blogv1-footer-bottom-inner">
    <span>© <?php echo esc_html(wp_date('Y')); ?> BeYourLover. All rights reserved.</span>
    <a href="<?php echo esc_url(home_url('/blog/')); ?>">Back to Blog ↑</a>
      </div>
  </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
