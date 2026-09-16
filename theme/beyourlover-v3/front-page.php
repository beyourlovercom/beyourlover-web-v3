<?php
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }
get_header();
$hero_products = byl_v3_showcase_products(3);
$popular_products = byl_v3_home_products(4);
$categories = array_filter([
    byl_v3_home_category('sex-toys-for-women'),
    byl_v3_home_category('sex-toys-for-men'),
    byl_v3_home_category('sex-toys-for-couples'),
]);
?>
<main id="main-content" class="byl-home" data-byl-template="home-v1">
  <section class="byl-home-hero">
    <div class="byl-container byl-home-hero-grid">
      <div class="byl-home-hero-copy" data-byl-motion="rise">
        <p class="byl-eyebrow">BEYOURLOVER®</p>
        <h1>BeYourLover Sex Toys</h1>
        <p class="byl-home-lede">Cute, original adult toys designed around intimacy, curiosity and pleasure — with a calmer way to discover what fits you.</p>
        <div class="byl-home-actions">
          <a class="byl-btn byl-btn-primary" href="<?php echo esc_url(home_url('/shop/')); ?>">Shop all</a>
          <a class="byl-btn byl-btn-secondary" href="<?php echo esc_url(home_url('/best-sellers/')); ?>">Best sellers</a>
        </div>
      </div>
      <div class="byl-home-hero-media" aria-label="Featured products">
        <?php foreach ($hero_products as $i => $product): ?>
          <a class="byl-home-hero-product byl-home-hero-product-<?php echo (int)$i + 1; ?>" href="<?php echo esc_url(get_permalink($product->get_id())); ?>">
            <img src="<?php echo esc_url(byl_v3_product_image_url($product)); ?>" alt="<?php echo esc_attr($product->get_name()); ?>">
            <span><?php echo esc_html($product->get_name()); ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="byl-home-section byl-home-categories">
    <div class="byl-container">
      <div class="byl-home-section-head">
        <div><p class="byl-eyebrow">Discover</p><h2>What can you get from BeYourLover?</h2></div>
        <p>Explore by who it is for, then narrow by the experience you want.</p>
      </div>
      <div class="byl-home-category-grid">
        <?php foreach ($categories as $category): ?>
          <a class="byl-home-category" href="<?php echo esc_url((string)$category['url']); ?>">
            <div class="byl-home-category-media">
              <?php if ($category['image'] !== ''): ?><img src="<?php echo esc_url($category['image']); ?>" alt=""><?php endif; ?>
            </div>
            <div><span><?php echo esc_html($category['count'].' products'); ?></span><h3><?php echo esc_html($category['name']); ?></h3></div>
            <?php echo byl_v3_icon('arrow'); ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="byl-home-section byl-home-explore">
    <div class="byl-container">
      <div class="byl-home-section-head">
        <div><p class="byl-eyebrow">Explore</p><h2>What's on BeYourLover?</h2></div>
        <p>Products, app experiences and editorial content share one clear path.</p>
      </div>
      <div class="byl-home-explore-grid">
        <a class="byl-home-feature byl-home-feature-brand" href="<?php echo esc_url(home_url('/new-arrivals/')); ?>">
          <span>New arrivals</span><h3>See what's new.</h3><p>Fresh launches and recent additions from the live catalog.</p><?php echo byl_v3_icon('arrow'); ?>
        </a>
        <a class="byl-home-feature" href="<?php echo esc_url(home_url('/app-guide/')); ?>">
          <span>Apps</span><h3>Connected experiences.</h3><p>Find the app or guide that matches your product.</p><?php echo byl_v3_icon('arrow'); ?>
        </a>
        <a class="byl-home-feature" href="<?php echo esc_url(home_url('/blog/')); ?>">
          <span>Editorial</span><h3>Learn at your pace.</h3><p>Guides, ideas and practical reading from BeYourLover.</p><?php echo byl_v3_icon('arrow'); ?>
        </a>
      </div>
    </div>
  </section>

  <section class="byl-home-section byl-home-products">
    <div class="byl-container">
      <div class="byl-home-section-head">
        <div><p class="byl-eyebrow">Best sellers</p><h2>Most Popular product on BeYourLover</h2></div>
        <a class="byl-link" href="<?php echo esc_url(home_url('/best-sellers/')); ?>">View all best sellers</a>
      </div>
      <div class="byl-product-grid">
        <?php foreach ($popular_products as $product): echo byl_v3_product_card($product); endforeach; ?>
      </div>
    </div>
  </section>

  <section class="byl-home-section byl-home-support">
    <div class="byl-container byl-home-support-inner">
      <div><p class="byl-eyebrow">Support</p><h2>Stay curious. Stay supported.</h2><p>Product help, discreet delivery information and guides stay one step away.</p></div>
      <a class="byl-btn byl-btn-secondary" href="<?php echo esc_url(home_url('/customer-help/')); ?>">Customer help</a>
    </div>
  </section>
</main>
<?php get_footer();
