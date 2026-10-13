<?php
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

/**
 * BYL V3 Cart Drawer Dev-only activation.
 * Normal Dev pages may open the Drawer via the cart icon; the preview query
 * additionally auto-opens it. WWW, non-staging, and missing Woo fail closed.
 * /cart/ remains the untouched Woo fallback; no payment/order takeover.
 */
function byl_v3_cart_drawer_runtime_enabled(): bool {
    if (!function_exists('WC') || !WC()) { return false; }
    $host = (string) wp_parse_url(home_url('/'), PHP_URL_HOST);
    return $host === 'dev.beyourlover.com'
        && function_exists('wp_get_environment_type')
        && wp_get_environment_type() === 'staging';
}

function byl_v3_cart_drawer_preview_enabled(): bool {
    return byl_v3_cart_drawer_runtime_enabled()
        && isset($_GET['byl_cart_drawer_preview'])
        && (string) wp_unslash($_GET['byl_cart_drawer_preview']) === '1';
}

add_action('wp_enqueue_scripts', static function (): void {
    if (!byl_v3_cart_drawer_runtime_enabled()) { return; }
    $dir = get_stylesheet_directory();
    $uri = get_stylesheet_directory_uri();
    wp_enqueue_style('byl-v3-cart-drawer', $uri.'/assets/css/cart-drawer.css', ['byl-v3-motion'], (string) filemtime($dir.'/assets/css/cart-drawer.css'));
    wp_enqueue_script('byl-v3-cart-drawer', $uri.'/assets/js/cart-drawer.js',
        function_exists('byl_v3_notification_cart_bridge_enabled') && byl_v3_notification_cart_bridge_enabled() ? ['byl-v3-notification-cart-bridge'] : [],
        (string) filemtime($dir.'/assets/js/cart-drawer.js'), true);
    wp_localize_script('byl-v3-cart-drawer', 'BYLCartDrawerPreview', [
        'cartApi' => esc_url_raw(rest_url('wc/store/v1/cart')),
        'nonce' => wp_create_nonce('wc_store_api'),
        'cartUrl' => function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/'),
        'checkoutUrl' => function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/checkout/'),
        'giftEnabled' => false,
        // Marketing/notification policy cannot be enabled via request parameters or JS.
        // The owner must separately approve an Andy Commerce-backed config contract.
        'marketingEnabled' => false,
        // M4 approved Dev-only canary. Independent of all promotional
        // campaigns and bounded by a server-side expiring v1-fingerprint gate.
        'shippingProgressEnabled' => class_exists('Andy_Commerce_M4_V2_Dev_Canary')
            && Andy_Commerce_M4_V2_Dev_Canary::enabled(),
        // Site-level, low-frequency copy is owned by Andy Commerce Settings.
        // The Cart only consumes sanitized data; never changes Woo totals.
        'cartSubtotalNotice' => class_exists('Andy_Commerce_Cart_Notice_Settings')
            ? Andy_Commerce_Cart_Notice_Settings::get()
            : ['enabled'=>true,
               'text'=>'Please check shipping costs, import duties and local taxes for your country or region. Final charges depend on your delivery address.'],
    ]);
    wp_script_add_data('byl-v3-cart-drawer', 'strategy', 'defer');
}, 30);

/**
 * D3.4 / D6 recommendation source:
 * 1. last three eligible products in Woo's first-party session-view cookie;
 * 2. fill remaining slots using verified Woo 30-day completed/processing unit sales.
 * Never use Woo's lifetime 'popularity' ranking for a 30-day claim.
 */
function byl_v3_cart_drawer_recent_ids(string $cookie): array {
    $ids = [];
    foreach (explode('|', $cookie) as $raw) {
        // Strict positive integer IDs; avoid user-supplied SQL/string input.
        if (!preg_match('/^[1-9][0-9]{0,9}$/D', $raw)) { continue; }
        $id = (int) $raw;
        if ($id > 0) { $ids = array_values(array_diff($ids, [$id])); $ids[] = $id; }
    }
    return array_slice(array_reverse($ids), 0, 15);
}

function byl_v3_cart_drawer_eligible_product(int $id): ?WC_Product {
    if ($id <= 0 || !function_exists('wc_get_product')) { return null; }
    $product = wc_get_product($id);
    if (!$product instanceof WC_Product || !in_array($product->get_type(), ['simple', 'variable'], true)
        || $product->get_status() !== 'publish' || !$product->is_purchasable()
        || !$product->is_in_stock() || $product->is_virtual()) { return null; }
    return $product;
}

function byl_v3_cart_drawer_monthly_best_seller_ids(): array {
    global $wpdb;
    if (!isset($wpdb) || !method_exists($wpdb, 'prepare') || !method_exists($wpdb, 'get_col')) { return []; }
    // Woo Analytics order/product lookups are read-only. No cached writes or
    // customer/order PII is fetched; only product IDs + unit counts aggregated.
    $lookup = $wpdb->prefix.'wc_order_product_lookup';
    $stats = $wpdb->prefix.'wc_order_stats';
    $cutoff = wp_date('Y-m-d H:i:s', time() - (30 * DAY_IN_SECONDS));
    $sql = $wpdb->prepare(
        "SELECT item.product_id
         FROM {$lookup} AS item
         INNER JOIN {$stats} AS orders ON orders.order_id = item.order_id
         WHERE item.date_created >= %s
           AND orders.status IN ('wc-processing','wc-completed')
         GROUP BY item.product_id
         HAVING SUM(item.product_qty) > 0
         ORDER BY SUM(item.product_qty) DESC, item.product_id ASC
         LIMIT 50",
        $cutoff
    );
    $ids = $wpdb->get_col($sql);
    return is_array($ids) ? array_values(array_filter(array_map('intval', $ids))) : [];
}

function byl_v3_cart_drawer_recommendations(): array {
    $recent = isset($_COOKIE['woocommerce_recently_viewed']) && is_string($_COOKIE['woocommerce_recently_viewed'])
        ? byl_v3_cart_drawer_recent_ids(wp_unslash($_COOKIE['woocommerce_recently_viewed']))
        : [];
    $result = [];
    $seen = [];
    foreach ($recent as $id) {
        $product = byl_v3_cart_drawer_eligible_product((int) $id);
        if (!$product) { continue; }
        $result[] = $product;
        $seen[$id] = true;
        if (count($result) === 3) { return $result; }
    }
    // A short history is filled by 30-day best sellers, never duplicate IDs.
    foreach (byl_v3_cart_drawer_monthly_best_seller_ids() as $id) {
        if (isset($seen[$id])) { continue; }
        $product = byl_v3_cart_drawer_eligible_product((int) $id);
        if (!$product) { continue; }
        $result[] = $product;
        $seen[$id] = true;
        if (count($result) === 3) { return $result; }
    }
    // A store may have fewer than three paid 30-day sellers (Dev currently
    // has one). Fill remaining positions with eligible catalog picks, ordered
    // by newest publish date; these are not claimed to have 30-day sales.
    if (function_exists('wc_get_products')) {
        $catalog = wc_get_products([
            'status' => 'publish', 'type' => ['simple', 'variable'],
            'limit' => 48, 'orderby' => 'date', 'order' => 'DESC',
        ]);
        if (is_array($catalog)) {
            foreach ($catalog as $candidate) {
                if (!$candidate instanceof WC_Product) { continue; }
                $id = (int) $candidate->get_id();
                if (isset($seen[$id])) { continue; }
                $product = byl_v3_cart_drawer_eligible_product($id);
                if (!$product) { continue; }
                $result[] = $product;
                $seen[$id] = true;
                if (count($result) === 3) { break; }
            }
        }
    }
    return $result;
}

/** Woo tracks this cookie only when its Recently Viewed widget is active.
 * On Dev product pages we complete the same session-only flow, without a new
 * plugin, database writes, persistent IDs, external services or WWW changes.
 */
add_action('template_redirect', static function (): void {
    if (!byl_v3_cart_drawer_runtime_enabled()) { return; }
    // Recommendations use session-view cookies. Never share personalized
    // Dev Drawer HTML through a guest full-page cache.
    nocache_headers();
    if (!function_exists('wc_setcookie') || !is_singular('product')) { return; }
    if (is_active_widget(false, false, 'woocommerce_recently_viewed_products', true)) { return; }
    $id = (int) get_queried_object_id();
    if ($id <= 0) { return; }
    $cookie = isset($_COOKIE['woocommerce_recently_viewed']) && is_string($_COOKIE['woocommerce_recently_viewed'])
        ? wp_unslash($_COOKIE['woocommerce_recently_viewed']) : '';
    $newest = byl_v3_cart_drawer_recent_ids($cookie);
    $list = array_reverse(array_values(array_diff($newest, [$id])));
    $list[] = $id;
    $list = array_slice($list, -15);
    wc_setcookie('woocommerce_recently_viewed', implode('|', $list));
}, 25);

/**
 * Marketing Slots V1 — structural reservation only. Promotion policy/config
 * belongs to Andy Commerce in a separate gated phase. No WP option, coupon,
 * gift line, third-party ad script, remote image or visitor parameter can
 * activate this slot. A blank/inactive slot takes zero vertical space.
 */
function byl_v3_cart_marketing_slots_enabled(): bool {
    return false;
}

add_action('wp_footer', static function (): void {
    if (!byl_v3_cart_drawer_runtime_enabled()) { return; }
    $recommendations = byl_v3_cart_drawer_recommendations();
    ?>
    <div id="byl-cart-drawer-v2" class="byl-drawer" data-byl-cart-drawer hidden>
      <button class="byl-drawer__scrim" data-drawer-close type="button" aria-label="Close cart"></button>
      <section class="byl-drawer__panel" role="dialog" aria-modal="true" aria-labelledby="byl-drawer-title" tabindex="-1">
        <aside class="byl-drawer__aside" aria-label="Recommended products">
          <div class="byl-drawer__aside-inner">
            <h2>You might also like</h2>
            <p>Recently viewed and thoughtfully selected picks</p>
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
                  <?php if ($product->is_type('simple')): ?>
                    <button type="button" class="byl-drawer__rec-add" data-drawer-add="<?php echo esc_attr((string)$product->get_id()); ?>">Add to cart</button>
                  <?php else: ?>
                    <a class="byl-drawer__rec-add" href="<?php echo esc_url(get_permalink($product->get_id())); ?>">View options</a>
                  <?php endif; ?>
                </article>
              <?php endforeach; ?>
            </div>
            <a class="byl-drawer__rec-browse" href="<?php echo esc_url(home_url('/shop/')); ?>">Explore best sellers →</a>
          </div>
        </aside>
        <div class="byl-drawer__cart">
          <div class="byl-drawer__scroll" data-drawer-scroll>
          <header class="byl-drawer__header">
            <h2 id="byl-drawer-title">Your Cart <span data-drawer-count></span></h2>
            <button class="byl-drawer__close" data-drawer-close type="button" aria-label="Close shopping cart">×</button>
            <nav class="byl-drawer__steps" aria-label="Checkout progress">
              <span class="byl-drawer__step is-active" aria-current="step">
                <span class="byl-drawer__step-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 8h14l1 12H4L5 8Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/></svg>
                </span>
                <span class="byl-drawer__step-label">Cart</span>
              </span>
              <span class="byl-drawer__step">
                <span class="byl-drawer__step-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M3 6h11v11H3z"/><path d="M14 10h4l3 3v4h-7"/><circle cx="7.5" cy="18" r="1.5"/><circle cx="17.5" cy="18" r="1.5"/></svg>
                </span>
                <span class="byl-drawer__step-label">Shipping</span>
              </span>
              <span class="byl-drawer__step">
                <span class="byl-drawer__step-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h3"/></svg>
                </span>
                <span class="byl-drawer__step-label">Payment</span>
              </span>
            </nav>
          </header>
            <div class="byl-drawer__preview-label">Dev design preview · gift offers are not active</div>
            <div class="byl-drawer__feedback" data-drawer-feedback role="status" aria-live="polite"></div>
            <div class="byl-drawer__delivery" data-drawer-delivery hidden></div>
            <?php // M5 shipping progress remains hidden unless a separately approved server policy enables it. ?>
            <div class="byl-drawer__m5-progress" data-drawer-m5-progress role="status" aria-live="polite" hidden></div>
            <section class="byl-drawer__empty" data-drawer-empty hidden>
              <div class="byl-drawer__empty-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" focusable="false"><circle cx="9" cy="20" r="1.5"/><circle cx="19" cy="20" r="1.5"/><path d="M2 3h2l3 13h13l2-9H5"/></svg></div>
              <h3>Your cart is empty</h3>
              <p>Explore our best sellers and find something for you.</p>
              <a class="byl-drawer__browse" href="<?php echo esc_url(home_url('/shop/')); ?>">SHOP BEST SELLERS</a>
            </section>
            <div class="byl-drawer__items" data-drawer-items></div>
            <section class="byl-drawer__mobile-recs">
              <h3>You might enjoy</h3>
              <div data-drawer-mobile-recs></div>
            </section>
            <?php // Campaign and service reminder slots are inactive by default and produce no rendered ad or claim. ?>
            <section class="byl-drawer__marketing" data-drawer-marketing-slots aria-label="Cart promotions" hidden>
              <div class="byl-drawer__marketing-banner" data-drawer-marketing-banner hidden></div>
              <div class="byl-drawer__marketing-note" data-drawer-marketing-note hidden></div>
            </section>
            <section class="byl-drawer__coupon" data-drawer-coupon-section hidden>
              <form data-drawer-coupon-form>
                <label for="byl-drawer-coupon-code">Promo / coupon code</label>
                <div><input id="byl-drawer-coupon-code" name="coupon_code" autocomplete="off" placeholder="Enter coupon code"><button type="submit">APPLY</button></div>
              </form>
              <div data-drawer-active-coupons></div>
            </section>
            <div class="byl-drawer__totals" data-drawer-totals hidden></div>
            <section class="byl-drawer__legal-payment" data-drawer-legal-payment aria-label="Purchase terms and payment options" hidden>
            <?php
            $byl_terms_url = function_exists('wc_get_page_permalink')
                ? (string) wc_get_page_permalink('terms') : '';
            if (!$byl_terms_url) { $byl_terms_url = home_url('/terms-and-conditions/'); }
            // Use existing WooPayments checkout card logos, not invented brands
            // or new external requests. PayPal is omitted unless verified active.
            $byl_payment_logos = [
                ['visa.svg', 'Visa'], ['mastercard.svg', 'Mastercard'],
                ['amex.svg', 'American Express'], ['discover.svg', 'Discover'],
                ['apple-pay.svg', 'Apple Pay'], ['google-pay.svg', 'Google Pay'],
            ];
            ?>
            <p class="byl-drawer__terms">By clicking Secure Checkout, you agree to the
              <a href="<?php echo esc_url($byl_terms_url); ?>" target="_blank" rel="noopener noreferrer">Terms and Conditions</a> of purchase.
            </p>
            <div class="byl-drawer__payment-methods" role="group" aria-label="Supported checkout payment methods">
              <?php foreach ($byl_payment_logos as [$byl_logo, $byl_alt]): ?>
                <?php if (is_file(WP_PLUGIN_DIR.'/woocommerce-payments/assets/images/cards/'.$byl_logo)): ?>
                <img src="<?php echo esc_url(plugins_url('woocommerce-payments/assets/images/cards/'.$byl_logo)); ?>"
                     alt="<?php echo esc_attr($byl_alt); ?>" loading="lazy" decoding="async">
                <?php endif; ?>
              <?php endforeach; ?>
            </div>
            </section>
          </div>
          <footer class="byl-drawer__footer" data-drawer-footer hidden>
            <div class="byl-drawer__total"><strong>Order total</strong><strong data-drawer-footer-total></strong></div>
            <a href="<?php echo esc_url(function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/checkout/')); ?>" data-drawer-checkout>SECURE CHECKOUT →</a>
          </footer>
        </div>
      </section>
    </div>
    <?php
}, 5);

// Load via Cart module so both legacy and Theme OS modular bootstraps remain compatible.
// The N6 adapter itself stays disabled unless BOTH Dev preview flags pass.
if (function_exists('get_stylesheet_directory')) {
    require_once get_stylesheet_directory().'/inc/notification-cart.php';
}
