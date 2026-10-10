<?php
/**
 * Cart-only product recommendations, always backed by real WooCommerce products.
 * Priority: native cross-sells -> product upsells -> related categories -> best sellers.
 */
declare(strict_types=1);
defined('ABSPATH') || exit;

function byl_v3_cart_recommendations(int $limit = 4): array {
    if (!function_exists('WC') || !WC() || !WC()->cart || !function_exists('wc_get_products')) { return []; }
    $items = WC()->cart->get_cart();
    if (!$items) { return []; }

    $excluded = [];
    $candidate_ids = [];
    $category_slugs = [];
    foreach ($items as $item) {
        $product_id = (int)($item['product_id'] ?? 0);
        $variation_id = (int)($item['variation_id'] ?? 0);
        if ($product_id > 0) { $excluded[$product_id] = true; }
        if ($variation_id > 0) { $excluded[$variation_id] = true; }
        $product = $item['data'] ?? null;
        if ($product && method_exists($product, 'get_upsell_ids')) {
            foreach ($product->get_upsell_ids() as $id) { $candidate_ids[] = (int)$id; }
        }
        if ($product_id && function_exists('get_the_terms')) {
            $terms = get_the_terms($product_id, 'product_cat');
            if (is_array($terms)) {
                foreach ($terms as $term) { if (!empty($term->slug)) { $category_slugs[(string)$term->slug] = true; } }
            }
        }
    }

    $cross_sells = method_exists(WC()->cart, 'get_cross_sells') ? (array)WC()->cart->get_cross_sells() : [];
    $candidate_ids = array_merge(array_map('intval', $cross_sells), $candidate_ids);
    $selected = [];
    $consider = static function ($product) use (&$selected, $excluded, $limit): void {
        if (!$product || !is_object($product) || !method_exists($product, 'get_id')) { return; }
        $id = (int)$product->get_id();
        if (!$id || isset($excluded[$id]) || isset($selected[$id])) { return; }
        // Direct add-to-cart is available for simple products without option selection.
        if (!$product->is_type('simple') || !$product->is_visible() || !$product->is_purchasable() || !$product->is_in_stock()) { return; }
        if ($product->get_status() !== 'publish') { return; }
        if (count($selected) < $limit) { $selected[$id] = $product; }
    };

    foreach (array_unique($candidate_ids) as $id) {
        if (count($selected) >= $limit) { break; }
        $consider(wc_get_product((int)$id));
    }
    if (count($selected) < $limit && $category_slugs) {
        $products = wc_get_products([
            'status'=>'publish', 'category'=>array_keys($category_slugs),
            'limit'=>16, 'exclude'=>array_keys($excluded), 'orderby'=>'popularity',
            'order'=>'DESC',
        ]);
        foreach ($products as $product) { $consider($product); }
    }
    if (count($selected) < $limit) {
        $products = wc_get_products([
            'status'=>'publish', 'limit'=>16, 'exclude'=>array_keys($excluded),
            'orderby'=>'popularity', 'order'=>'DESC',
        ]);
        foreach ($products as $product) { $consider($product); }
    }
    return array_values($selected);
}

function byl_v3_render_cart_recommendations(): void {
    if (!function_exists('is_cart') || !is_cart()) { return; }
    $products = byl_v3_cart_recommendations(4);
    if (!$products) { return; }
    ?>
    <section class="byl-cart-recommendations" aria-label="<?php esc_attr_e('Recommended products', 'beyourlover-v3'); ?>">
      <div class="byl-cart-recommendations__heading">
        <h2><?php esc_html_e('You may also like', 'beyourlover-v3'); ?></h2>
        <a href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/')); ?>"><?php esc_html_e('See more', 'beyourlover-v3'); ?> →</a>
      </div>
      <div class="byl-cart-recommendations__items">
        <?php foreach ($products as $product) : ?>
        <article class="byl-cart-recommendation">
          <a class="byl-cart-recommendation__image" href="<?php echo esc_url(get_permalink($product->get_id())); ?>">
            <?php echo wp_kses_post($product->get_image('woocommerce_thumbnail')); ?>
          </a>
          <div class="byl-cart-recommendation__detail">
            <a class="byl-cart-recommendation__name" href="<?php echo esc_url(get_permalink($product->get_id())); ?>"><?php echo esc_html($product->get_name()); ?></a>
            <div class="byl-cart-recommendation__price"><?php echo wp_kses_post($product->get_price_html()); ?></div>
            <a class="byl-cart-recommendation__add" href="<?php echo esc_url(add_query_arg('add-to-cart', $product->get_id(), wc_get_cart_url())); ?>" aria-label="<?php echo esc_attr(sprintf(__('Add %s to cart', 'beyourlover-v3'), $product->get_name())); ?>"><?php esc_html_e('Add to cart', 'beyourlover-v3'); ?></a>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </section>
    <?php
}
add_action('woocommerce_after_cart', 'byl_v3_render_cart_recommendations', 30);

/**
 * Read-only promotional message for the classic Woo Cart totals.
 *
 * Important: the V3 Cart uses [woocommerce_cart], not the Blocks cart.
 * Its Woo customer destination and calculated shipping package must be the
 * authority for thresholds and rates, never another Store API cart state.
 */
function byl_v3_cart_native_promotion_state(): array {
    if (!function_exists('WC') || !WC() || !WC()->cart ||
        !class_exists('WC_Shipping_Zones') ||
        !class_exists('Andy_Commerce_Shipping_Promotion_Policy')) { return []; }

    $policy = 'Andy_Commerce_Shipping_Promotion_Policy';
    $settings = $policy::get_persisted_settings();
    if (!is_array($settings)) { return []; }

    $cart = WC()->cart;
    if (!method_exists($cart, 'get_shipping_packages') || !method_exists($cart, 'get_displayed_subtotal')) { return []; }
    $packages = (array) $cart->get_shipping_packages();
    // Multi-package checkout may have different zones/rates; do not collapse
    // them into one potentially false free-shipping promise.
    if (count($packages) !== 1) { return []; }
    $package = $packages[0] ?? null;
    if (!is_array($package)) { return []; }

    $zone = WC_Shipping_Zones::get_zone_matching_package($package);
    if (!is_object($zone) || !method_exists($zone, 'get_id')) { return []; }
    $zone_id = (int) $zone->get_id();
    $threshold = $policy::get_persisted_zone_effective_threshold($zone_id);
    if (null === $threshold) { return []; }

    // Read the same zone catalog Woo already exposes. Do not directly
    // instantiate/rehydrate zone shipping methods a second time here:
    // it can fail under CLI and some plugin-managed method contexts.
    $zones = WC_Shipping_Zones::get_zones();
    $zone_info = (array) ($zones[$zone_id] ?? []);
    $free_supported = false;
    foreach ((array) ($zone_info['shipping_methods'] ?? []) as $method) {
        $id = is_object($method) && method_exists($method, 'get_method_id')
            ? $method->get_method_id() : (is_object($method) ? ($method->id ?? '') : '');
        if ('free_shipping' === $id && 'no' !== (string) ($method->enabled ?? 'yes')) {
            $free_supported = true;
            break;
        }
    }

    $rates = [];
    $shipping = WC()->shipping();
    if ($shipping && method_exists($shipping, 'get_packages')) {
        $calculated = (array) $shipping->get_packages();
        $rates = (array) ($calculated[0]['rates'] ?? []);
    }
    $session = WC()->session;
    $chosen = $session && method_exists($session, 'get')
        ? (array) $session->get('chosen_shipping_methods', []) : [];
    $selected = isset($chosen[0]) ? (string) $chosen[0] : '';
    $free_available = false;
    $selected_free = false;
    foreach ($rates as $rate_key => $rate) {
        if (!is_object($rate) || !method_exists($rate, 'get_method_id') || !method_exists($rate, 'get_cost')) { continue; }
        if ('free_shipping' !== $rate->get_method_id() || (float) $rate->get_cost() !== 0.0) { continue; }
        $free_available = true;
        if ((string) $rate_key === $selected) { $selected_free = true; }
    }

    // Rest-of-world and dynamically configured zones may not be in the
    // standard catalog, but an actual zero-cost Woo rate is definitive.
    if ($free_available) { $free_supported = true; }

    $decimals = function_exists('wc_get_price_decimals') ? (int) wc_get_price_decimals() : 2;
    $threshold = round((float) $threshold, $decimals);
    $subtotal = round((float) $cart->get_displayed_subtotal(), $decimals);
    $eligible = $subtotal >= $threshold;
    $applied = method_exists($cart, 'get_applied_coupons') ? array_values((array) $cart->get_applied_coupons()) : [];
    $applied_code = isset($applied[0]) ? (string) $applied[0] : '';
    $policy_code = $policy::normalize_code((string) $settings['free_shipping_code']);
    $code_applied = $applied_code !== '' && $policy::normalize_code($applied_code) === $policy_code;
    $requires_code = 'require_code' === ($settings['mode'] ?? '');

    if (!$free_supported) { $state = 'shipping_unavailable'; }
    elseif (!$eligible) { $state = 'below_threshold'; }
    elseif ($requires_code && !$code_applied) { $state = $applied_code !== '' ? 'require_code_other' : 'require_code_available'; }
    elseif (!$free_available) { $state = 'shipping_unavailable'; }
    elseif (!$selected_free) { $state = 'free_shipping_available'; }
    else { $state = $requires_code ? 'require_code_unlocked' : 'automatic_unlocked'; }

    return [
        'active' => true,
        'state' => $state,
        'threshold' => $threshold,
        'subtotal' => $subtotal,
        'remaining' => max(0.0, round($threshold - $subtotal, $decimals)),
        'eligible' => $eligible,
        'unlocked' => $selected_free,
        'policy_code' => $policy_code,
        'applied_code' => $applied_code,
        'has_code' => $applied_code !== '',
        'zone_id' => $zone_id,
        'free_rate_available' => $free_available,
        'free_rate_selected' => $selected_free,
    ];
}

function byl_v3_render_native_cart_promotion_context(): void {
    if (!function_exists('is_cart') || !is_cart()) { return; }
    $state = byl_v3_cart_native_promotion_state();
    if (!$state) { return; }
    echo '<tr class="byl-cart-promotion-context" hidden style="display:none"><td colspan="2"><span data-byl-native-promotion="' .
        esc_attr(wp_json_encode($state)) . '"></span></td></tr>';
}
add_action('woocommerce_cart_totals_before_order_total', 'byl_v3_render_native_cart_promotion_context', 5);
