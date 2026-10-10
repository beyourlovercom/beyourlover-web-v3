<?php
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

/**
 * BYL V3 Cart Drawer D3.1 preview gate.
 * No global cart takeover and no external orders or payment state changes.
 */
function byl_v3_cart_drawer_preview_enabled(): bool {
    if (!function_exists('WC') || !WC()) { return false; }
    $host = (string) wp_parse_url(home_url('/'), PHP_URL_HOST);
    return $host === 'dev.beyourlover.com'
        && isset($_GET['byl_cart_drawer_preview'])
        && (string) wp_unslash($_GET['byl_cart_drawer_preview']) === '1';
}

add_action('wp_enqueue_scripts', static function (): void {
    if (!byl_v3_cart_drawer_preview_enabled()) { return; }
    $dir = get_stylesheet_directory();
    $uri = get_stylesheet_directory_uri();
    wp_enqueue_style('byl-v3-cart-drawer', $uri.'/assets/css/cart-drawer.css', ['byl-v3-motion'], (string) filemtime($dir.'/assets/css/cart-drawer.css'));
    wp_enqueue_script('byl-v3-cart-drawer', $uri.'/assets/js/cart-drawer.js', [], (string) filemtime($dir.'/assets/js/cart-drawer.js'), true);
    wp_localize_script('byl-v3-cart-drawer', 'BYLCartDrawerPreview', [
        'cartApi' => esc_url_raw(rest_url('wc/store/v1/cart')),
        'nonce' => wp_create_nonce('wc_store_api'),
        'cartUrl' => function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/'),
        'checkoutUrl' => function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/checkout/'),
        'giftEnabled' => false,
    ]);
    wp_script_add_data('byl-v3-cart-drawer', 'strategy', 'defer');
}, 30);

function byl_v3_cart_drawer_recommendations(): array {
    if (!function_exists('wc_get_products')) { return []; }
    $products = wc_get_products(['status'=>'publish', 'type'=>'simple', 'limit'=>12, 'orderby'=>'popularity', 'order'=>'DESC']);
    if (!is_array($products)) { return []; }
    $result = [];
    foreach ($products as $product) {
        if (!$product instanceof WC_Product || !$product->is_purchasable() || !$product->is_in_stock() || $product->is_virtual()) { continue; }
        $result[] = $product;
        if (count($result) === 3) { break; }
    }
    return $result;
}

add_action('wp_footer', static function (): void {
    if (!byl_v3_cart_drawer_preview_enabled()) { return; }
    $recommendations = byl_v3_cart_drawer_recommendations();
    ?>
    <div id="byl-cart-drawer-v2" class="byl-drawer" data-byl-cart-drawer hidden>
      <button class="byl-drawer__scrim" data-drawer-close type="button" aria-label="Close cart"></button>
      <section class="byl-drawer__panel" role="dialog" aria-modal="true" aria-labelledby="byl-drawer-title" tabindex="-1">
        <aside class="byl-drawer__aside" aria-label="Recommended products">
          <div class="byl-drawer__aside-inner">
            <h2>You might also like</h2>
            <p>Frequently bought together</p>
            <div class="byl-drawer__recommendations" data-drawer-recommendations>
              <?php foreach ($recommendations as $product): ?>
                <article class="byl-drawer__rec" data-product-id="<?php echo esc_attr((string)$product->get_id()); ?>">
                  <a href="<?php echo esc_url(get_permalink($product->get_id())); ?>" class="byl-drawer__rec-image">
                    <img src="<?php echo esc_url(byl_v3_product_image_url($product)); ?>" alt="" loading="lazy">
                  </a>
                  <div class="byl-drawer__rec-info">
                    <a href="<?php echo esc_url(get_permalink($product->get_id())); ?>"><?php echo esc_html($product->get_name()); ?></a>
                    <span><?php echo wp_kses_post($product->get_price_html()); ?></span>
                  </div>
                  <button type="button" class="byl-drawer__rec-add" data-drawer-add="<?php echo esc_attr((string)$product->get_id()); ?>">Add to cart</button>
                </article>
              <?php endforeach; ?>
            </div>
            <a class="byl-drawer__rec-browse" href="<?php echo esc_url(home_url('/shop/')); ?>">Explore best sellers →</a>
          </div>
        </aside>
        <div class="byl-drawer__cart">
          <header class="byl-drawer__header">
            <h2 id="byl-drawer-title">Your Cart <span data-drawer-count></span></h2>
            <button class="byl-drawer__close" data-drawer-close type="button" aria-label="Close shopping cart">×</button>
            <nav class="byl-drawer__steps" aria-label="Checkout steps">
              <span class="is-active" aria-label="Cart step">♧</span><span aria-label="Shipping step">⌖</span><span aria-label="Payment step">▤</span>
            </nav>
          </header>
          <div class="byl-drawer__scroll" data-drawer-scroll>
            <div class="byl-drawer__preview-label">Dev design preview · gift offers are not active</div>
            <div class="byl-drawer__feedback" data-drawer-feedback role="status" aria-live="polite"></div>
            <div class="byl-drawer__delivery" data-drawer-delivery hidden></div>
            <section class="byl-drawer__empty" data-drawer-empty hidden>
              <div class="byl-drawer__empty-icon" aria-hidden="true">♧</div>
              <h3>Your cart is empty</h3>
              <p>Explore our best sellers and find something for you.</p>
              <a class="byl-drawer__browse" href="<?php echo esc_url(home_url('/shop/')); ?>">SHOP BEST SELLERS</a>
            </section>
            <div class="byl-drawer__items" data-drawer-items></div>
            <section class="byl-drawer__mobile-recs">
              <h3>You might enjoy</h3>
              <div data-drawer-mobile-recs></div>
            </section>
            <section class="byl-drawer__coupon" data-drawer-coupon-section hidden>
              <form data-drawer-coupon-form>
                <label for="byl-drawer-coupon-code">Promo / coupon code</label>
                <div><input id="byl-drawer-coupon-code" name="coupon_code" autocomplete="off" placeholder="Enter coupon code"><button type="submit">APPLY</button></div>
              </form>
              <div data-drawer-active-coupons></div>
            </section>
            <div class="byl-drawer__totals" data-drawer-totals hidden></div>
          </div>
          <footer class="byl-drawer__footer" data-drawer-footer hidden>
            <div class="byl-drawer__total"><strong>Order total</strong><strong data-drawer-footer-total></strong></div>
            <a href="<?php echo esc_url(function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/checkout/')); ?>" data-drawer-checkout>SECURE CHECKOUT →</a>
            <p>Shipping and payment are confirmed securely at checkout.</p>
          </footer>
        </div>
      </section>
    </div>
    <?php
}, 5);
