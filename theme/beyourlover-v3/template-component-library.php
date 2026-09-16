<?php
/** Template Name: BYL Component Library */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }
get_header();
$products = byl_v3_showcase_products(4);
?>
<main id="main-content" class="byl-component-library" data-byl-component-library="v1">
  <section class="byl-component-hero">
    <div class="byl-container byl-component-hero-grid">
      <div>
        <p class="byl-eyebrow">G5 · Component Library V1</p>
        <h1 class="byl-display-l">Reusable by design.<br>Calm by default.</h1>
        <p class="byl-body-l">The shared UI layer for commerce, editorial, docs and support. Built on G3 tokens and independent of Shoptimizer runtime.</p>
      </div>
      <div class="byl-component-summary" aria-label="Component summary">
        <span><strong>4</strong> families</span><span><strong>Woo</strong> data</span><span><strong>390–1440</strong> responsive</span><span><strong>AA</strong> focus</span>
      </div>
    </div>
  </section>

  <section class="byl-component-section" id="foundation">
    <div class="byl-container">
      <div class="byl-component-head">
        <div><p class="byl-eyebrow">Foundation</p><h2 class="byl-heading-xl">Actions and signals.</h2></div>
        <p class="byl-body-m">Buttons, icon actions, badges, chips and links share one interaction language.</p>
      </div>
      <div class="byl-component-grid">
        <article class="byl-demo-panel"><h3>Buttons</h3><div class="byl-cluster"><button class="byl-btn byl-btn-primary">Primary</button><button class="byl-btn byl-btn-secondary">Secondary</button><button class="byl-btn byl-btn-quiet">Quiet</button><button class="byl-btn byl-btn-secondary" disabled>Disabled</button></div></article>
        <article class="byl-demo-panel"><h3>Signals</h3><div class="byl-cluster"><span class="byl-badge byl-badge-brand">Sale</span><span class="byl-badge byl-badge-success">In stock</span><span class="byl-chip">Featured</span><span class="byl-chip byl-chip-neutral">Neutral</span></div></article>
        <article class="byl-demo-panel"><h3>Icon actions</h3><div class="byl-cluster"><button class="byl-icon-btn" aria-label="Save product"><?php echo byl_v3_icon('heart'); ?></button><button class="byl-icon-btn" aria-label="Open bag"><?php echo byl_v3_icon('bag'); ?></button><a class="byl-link" href="#commerce">Text link</a></div></article>
      </div>
    </div>
  </section>

  <section class="byl-component-section" id="commerce">
    <div class="byl-container">
      <div class="byl-component-head">
        <div><p class="byl-eyebrow">Commerce</p><h2 class="byl-heading-xl">Product cards, real Woo data.</h2></div>
        <p class="byl-body-m">Image, SKU, title, rating, price and availability are rendered from WooCommerce without Shoptimizer markup.</p>
      </div>
      <?php if ($products): ?>
        <div class="byl-product-grid">
          <?php foreach ($products as $product): echo byl_v3_product_card($product); endforeach; ?>
        </div>
      <?php else: ?>
        <div class="byl-empty"><?php echo byl_v3_icon('bag'); ?><h3 class="byl-heading-m">No products available</h3><p class="byl-body-s">The component fails closed when Woo data is unavailable.</p></div>
      <?php endif; ?>
    </div>
  </section>

  <section class="byl-component-section" id="content">
    <div class="byl-container">
      <div class="byl-component-head">
        <div><p class="byl-eyebrow">Content</p><h2 class="byl-heading-xl">One card language, different jobs.</h2></div>
        <p class="byl-body-m">Editorial, Docs and Support cards share spacing and motion while preserving their own information hierarchy.</p>
      </div>
      <div class="byl-component-grid">
        <a class="byl-content-card is-editorial" href="/blog/"><?php echo byl_v3_icon('book'); ?><span class="byl-content-label">Editorial</span><h3>Editorial story</h3><p>Long-form discovery with restrained hierarchy and a generous reading rhythm.</p><footer><span>Story · 5 min</span><?php echo byl_v3_icon('arrow'); ?></footer></a>
        <a class="byl-content-card is-docs" href="/docs/"><?php echo byl_v3_icon('book'); ?><span class="byl-content-label">Guide</span><h3>Setup & care guide</h3><p>Docs-ready navigation without depending on BetterDocs visual runtime.</p><footer><span>6 min · Step by step</span><?php echo byl_v3_icon('arrow'); ?></footer></a>
        <a class="byl-content-card is-support" href="/customer-help/"><?php echo byl_v3_icon('info'); ?><span class="byl-content-label">Help center</span><h3>Customer support</h3><p>Clear utility entry points for shipping, returns, account and product help.</p><footer><span>Shipping · Returns · Account</span><?php echo byl_v3_icon('arrow'); ?></footer></a>
      </div>
    </div>
  </section>

  <section class="byl-component-section" id="forms">
    <div class="byl-container">
      <div class="byl-component-head">
        <div><p class="byl-eyebrow">Forms & states</p><h2 class="byl-heading-xl">Utility stays quiet.</h2></div>
        <p class="byl-body-m">Form controls and system feedback prioritize clarity, focus visibility and low visual noise.</p>
      </div>
      <div class="byl-component-grid">
        <article class="byl-demo-panel byl-demo-panel-quiet byl-stack"><h3>Form primitives</h3><div class="byl-field"><label for="g5-email">Email</label><input class="byl-input" id="g5-email" type="email" placeholder="name@example.com"><span class="byl-field-help">Used for account, checkout and support patterns.</span></div><div class="byl-field"><label for="g5-select">Preference</label><select class="byl-select" id="g5-select"><option>Discreet delivery</option><option>Standard delivery</option></select></div><label class="byl-check"><input type="checkbox" checked><span>Remember this preference</span></label></article>
        <article class="byl-demo-panel byl-demo-panel-quiet byl-stack"><h3>System feedback</h3><div class="byl-notice byl-notice-info"><?php echo byl_v3_icon('info'); ?><span>Helpful information stays visible without competing with the primary action.</span></div><div class="byl-notice byl-notice-success"><?php echo byl_v3_icon('check'); ?><span>Saved successfully.</span></div><div class="byl-notice byl-notice-danger"><?php echo byl_v3_icon('info'); ?><span>Something needs attention before continuing.</span></div></article>
        <article class="byl-demo-panel byl-demo-panel-quiet byl-stack"><h3>Empty & loading</h3><div class="byl-empty"><?php echo byl_v3_icon('bag'); ?><strong>Nothing here yet</strong><span class="byl-body-s">A calm empty state with one clear next action.</span></div><div class="byl-skeleton" style="height:18px;width:72%" aria-hidden="true"></div><div class="byl-skeleton" style="height:96px;width:100%" aria-hidden="true"></div></article>
      </div>
    </div>
  </section>
</main>
<?php get_footer();
