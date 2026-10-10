<?php
/**
 * BYL Free Shipping — Dev-first public information page.
 *
 * Read-only session-driven progress. WooCommerce Cart / Checkout remain
 * authoritative for actual delivery-zone eligibility and selected rates.
 * No fake amounts; empty/unknown sessions are clearly disclosed.
 */
declare(strict_types=1);
defined('ABSPATH') || exit;

// This page includes cart-dependent information; never store shared HTML
// responses that could show a previous visitor's cart subtotal or address.
if (!headers_sent()) { nocache_headers(); }

$state = ['kind' => 'unknown', 'subtotal' => 0.0, 'threshold' => null,
    'remaining' => null, 'zone' => null, 'chosen_free' => false, 'currency' => '$'];

if (function_exists('get_woocommerce_currency_symbol')) {
    $state['currency'] = html_entity_decode((string) get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8');
}
if (function_exists('WC') && WC() && WC()->cart && class_exists('WC_Shipping_Zones') &&
    class_exists('Andy_Commerce_Shipping_Promotion_Policy')) {
    $cart = WC()->cart;
    $policy = 'Andy_Commerce_Shipping_Promotion_Policy';
    $settings = $policy::get_persisted_settings();
    $count = method_exists($cart, 'get_cart_contents_count') ? (int)$cart->get_cart_contents_count() : 0;
    if ($count <= 0) {
        $state['kind'] = 'empty';
    } elseif (is_array($settings) && method_exists($cart, 'get_shipping_packages') &&
        method_exists($cart, 'get_displayed_subtotal')) {
        $packages = (array) $cart->get_shipping_packages();
        if (count($packages) === 1 && is_array(reset($packages))) {
            $package = reset($packages);
            $zone = WC_Shipping_Zones::get_zone_matching_package($package);
            $zone_id = $zone && method_exists($zone, 'get_id') ? (int)$zone->get_id() : null;
            $threshold = $zone_id !== null ? $policy::get_persisted_zone_effective_threshold($zone_id) : null;
            if ($threshold !== null) {
                $zones = WC_Shipping_Zones::get_zones();
                $shipping_methods = (array) ($zones[$zone_id]['shipping_methods'] ?? []);
                $supports_free = false;
                foreach ($shipping_methods as $method) {
                    $method_id = is_object($method) && method_exists($method, 'get_method_id')
                        ? (string)$method->get_method_id() : (is_object($method) ? (string)($method->id ?? '') : '');
                    if ($method_id === 'free_shipping' && (string)($method->enabled ?? 'yes') !== 'no') {
                        $supports_free = true;
                        break;
                    }
                }
                $decimals = function_exists('wc_get_price_decimals') ? (int)wc_get_price_decimals() : 2;
                $subtotal = round((float)$cart->get_displayed_subtotal(), $decimals);
                $threshold = round((float)$threshold, $decimals);
                $rates = [];
                if (method_exists(WC(), 'shipping') && WC()->shipping() &&
                    method_exists(WC()->shipping(), 'get_packages')) {
                    $rated_packages = (array) WC()->shipping()->get_packages();
                    $rates = (array) ($rated_packages[0]['rates'] ?? []);
                }
                $chosen = WC()->session && method_exists(WC()->session, 'get')
                    ? (array) WC()->session->get('chosen_shipping_methods', []) : [];
                $selected = (string)($chosen[0] ?? '');
                $available_free = false;
                $chosen_free = false;
                foreach ($rates as $key => $rate) {
                    if (is_object($rate) && method_exists($rate, 'get_method_id') &&
                        method_exists($rate, 'get_cost') && $rate->get_method_id() === 'free_shipping' &&
                        (float)$rate->get_cost() === 0.0) {
                        $available_free = true;
                        if ((string)$key === $selected) { $chosen_free = true; }
                    }
                }
                if ($available_free) { $supports_free = true; }
                $coupon_ok = true;
                if (($settings['mode'] ?? '') === 'require_code') {
                    $policy_code = $policy::normalize_code((string)($settings['free_shipping_code'] ?? ''));
                    $coupons = method_exists($cart, 'get_applied_coupons')
                        ? (array)$cart->get_applied_coupons() : [];
                    $coupon_ok = false;
                    foreach ($coupons as $coupon) {
                        if ($policy::normalize_code((string)$coupon) === $policy_code) {
                            $coupon_ok = true; break;
                        }
                    }
                }
                $eligible = ($subtotal >= $threshold) && $coupon_ok;
                $state = [
                    'kind' => !$supports_free ? 'unavailable' :
                        ($subtotal < $threshold ? 'below' :
                         (!$coupon_ok ? 'code_required' : 'unlocked')),
                    'subtotal' => $subtotal,
                    'threshold' => $threshold,
                    'remaining' => max(0.0, round($threshold - $subtotal, $decimals)),
                    'zone' => $zone_id,
                    'chosen_free' => $chosen_free,
                    'free_rate_available' => $available_free,
                    'currency' => $state['currency'],
                ];
            }
        }
    }
} elseif (function_exists('WC') && WC() && WC()->cart &&
    method_exists(WC()->cart, 'get_cart_contents_count') &&
    (int)WC()->cart->get_cart_contents_count() <= 0) {
    $state['kind'] = 'empty';
}

$threshold = isset($state['threshold']) ? (float)$state['threshold'] : null;
$price = static function ($value, bool $whole = false) use ($state): string {
    $amount = (float)$value;
    $decimals = $whole && floor($amount) === $amount ? 0 : 2;
    return (string)$state['currency'] . number_format($amount, $decimals, '.', ',');
};
$cart_link = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');
$shop_link = home_url('/shop/');
get_header();
?>
<main id="main-content" class="byl-free" aria-labelledby="byl-free-title"
      data-byl-free-kind="<?php echo esc_attr((string)$state['kind']); ?>">
  <div class="byl-free__shell">
    <nav class="byl-free__crumb" aria-label="Breadcrumb">
      <a href="<?php echo esc_url(home_url('/')); ?>">Home</a><span aria-hidden="true">/</span>Free Shipping
    </nav>
    <section class="byl-free__hero">
      <div class="byl-free__intro">
        <p class="byl-free__eyebrow">A little extra joy, on us</p>
        <h1 id="byl-free-title">Free Shipping</h1>
        <p class="byl-free__summary">Your favorites deserve a lovely arrival. Reach the free-shipping goal for your delivery destination, and enjoy a little extra on us.</p>
        <div class="byl-free__links">
          <a class="byl-free__button" href="<?php echo esc_url($shop_link); ?>">Continue shopping <span aria-hidden="true">→</span></a>
          <a class="byl-free__textlink" href="#how-free-shipping-works">How it works</a>
        </div>
      </div>
      <div class="byl-free__art" aria-hidden="true">
        <div class="byl-free__rings"></div>
        <svg viewBox="0 0 430 335" class="byl-free__gift" fill="none" xmlns="http://www.w3.org/2000/svg">
          <ellipse cx="204" cy="285" rx="124" ry="19" fill="#e9cbd3" opacity=".65"/>
          <path d="m107 147 99-48 123 37-106 51z" fill="#e8aebe" stroke="#92405c" stroke-width="2"/>
          <path d="m107 147 116 40v97l-116-43z" fill="#f2c9d1" stroke="#92405c" stroke-width="2"/>
          <path d="m223 187 106-51v107l-106 41z" fill="#e7a9bc" stroke="#92405c" stroke-width="2"/>
          <path d="m160 119 119 42-41 19-119-43z" fill="#fff5ed" opacity=".92"/>
          <path d="m161 120 118 41v43l-41 20v-43L120 139z" fill="#fff8f2" stroke="#d6aab5" stroke-width="1.3"/>
          <path d="M159 119v46m79 15 41-19" stroke="#a95272" stroke-width="3"/>
          <path d="M120 173v29m11-25v29m13-24v29" stroke="#c6879d" stroke-width="3" stroke-linecap="round"/>
          <rect x="245" y="224" width="59" height="25" rx="2" fill="#fff7f5" stroke="#a35e76"/>
          <path d="M254 233h38m-38 7h29" stroke="#c78da1" stroke-width="2"/>
          <path d="m84 107 6-23 14 17M303 96l8-21 14 21M342 207l17-4-5 20" stroke="#b35f7c" stroke-width="3" stroke-linecap="round"/>
          <circle cx="82" cy="225" r="5" fill="#d38ca5"/><circle cx="325" cy="266" r="5" fill="#dfadbe"/>
        </svg>
        <span class="byl-free__seal"><span>♡</span><small>Made with<br>care</small></span>
      </div>
    </section>
    <section class="byl-free__progress-section" aria-labelledby="byl-free-progress-title">
      <div class="byl-free__panel">
        <div class="byl-free__panel-top"><span>Your shipping progress</span><span>Based on your delivery zone</span></div>
        <?php if (in_array($state['kind'], ['below', 'unlocked', 'code_required'], true) && $threshold !== null) : ?>
          <h2 id="byl-free-progress-title" class="byl-free__goal">Free shipping on orders over <?php echo esc_html($price($threshold, true)); ?></h2>
          <?php if ($state['kind'] === 'below') : ?>
            <p class="byl-free__state" aria-live="polite">Add <strong><?php echo esc_html($price($state['remaining'])); ?></strong> more for free shipping</p>
          <?php elseif ($state['kind'] === 'code_required') : ?>
            <p class="byl-free__state">You've reached the order minimum. Apply the eligible shipping code in your cart to unlock free shipping.</p>
          <?php else : ?>
            <p class="byl-free__state byl-free__state--success" aria-live="polite">🎉 Congrats! You've unlocked free shipping.</p>
            <?php if (!$state['chosen_free']) : ?>
              <p class="byl-free__help">Select the Free Shipping delivery method in your Cart or Checkout to apply your savings.</p>
            <?php else : ?>
              <p class="byl-free__help">Free shipping is selected for your current delivery address.</p>
            <?php endif; ?>
          <?php endif; ?>
          <?php $progress = $threshold > 0 ? min(100.0, max(0.0, (float)$state['subtotal'] / $threshold * 100.0)) : 100.0; ?>
          <div class="byl-free__meter<?php echo $state['kind'] === 'unlocked' ? ' byl-free__meter--complete' : ''; ?>"
               role="progressbar" aria-label="Progress toward free shipping"
               aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr((string)round($progress)); ?>">
            <span style="width:<?php echo esc_attr(number_format($progress, 2, '.', '')); ?>%"></span>
          </div>
          <div class="byl-free__meter-label"><span>Cart subtotal: <strong><?php echo esc_html($price($state['subtotal'])); ?></strong></span><span>Goal: <strong><?php echo esc_html($price($threshold, true)); ?></strong></span></div>
        <?php else : ?>
          <h2 id="byl-free-progress-title" class="byl-free__goal">Your shipping goal, made simple</h2>
          <p class="byl-free__state" aria-live="polite">
            <?php if ($state['kind'] === 'empty') : ?>
              Your cart is empty. Add your favorites to see your free-shipping progress.
            <?php elseif ($state['kind'] === 'unavailable') : ?>
              Free shipping isn't available for this delivery zone. Check your shipping options in Cart.
            <?php else : ?>
              Your free-shipping goal depends on your delivery address. Review your cart to see the applicable shipping options.
            <?php endif; ?>
          </p>
          <p class="byl-free__help">Your Cart and Checkout always show the actual available rates for your destination.</p>
        <?php endif; ?>
        <div class="byl-free__panel-bottom"><p>Shipping offers depend on your actual delivery address and eligible services.</p>
          <a href="<?php echo esc_url(in_array($state['kind'], ['empty','below'], true) ? $shop_link : $cart_link); ?>"><?php echo in_array($state['kind'], ['empty','below'], true) ? 'Explore best sellers' : 'View your cart'; ?> →</a>
        </div>
      </div>
    </section>
    <?php
    // Dynamic policy amounts from Andy Commerce; labels describe geographic
    // coverage audited against WooCommerce, not another editable config source.
    $fs_zones = class_exists('WC_Shipping_Zones') ? WC_Shipping_Zones::get_zones() : [];
    $fs_policy = class_exists('Andy_Commerce_Shipping_Promotion_Policy')
        ? 'Andy_Commerce_Shipping_Promotion_Policy' : null;
    $fs_groups = [
        ['zone'=>1, 'title'=>'Hong Kong', 'region'=>'Hong Kong', 'detail'=>'Hong Kong SAR is its own zero-minimum Free Shipping region.'],
        ['zone'=>5, 'title'=>'Asia &amp; most of the U.S.', 'region'=>'Most U.S. states and Asian destinations', 'detail'=>'Mainland U.S. destinations excluding Alaska and Hawaii; Asian destinations except Hong Kong. The United Arab Emirates currently matches this region.'],
        ['zone'=>4, 'title'=>'Extended destinations', 'region'=>'Alaska, Hawaii, Puerto Rico and Mexico', 'detail'=>'U.S. Alaska and Hawaii, Puerto Rico and Mexico. Country/region eligibility is calculated from your exact destination.'],
        ['zone'=>3, 'title'=>'Selected destinations', 'region'=>'Russia, Venezuela, North Macedonia, Moldova, Costa Rica and Argentina', 'detail'=>'Russia, Venezuela, North Macedonia, Moldova, Costa Rica and Argentina. Available delivery methods and local restrictions still apply.'],
    ];
    $fs_countries = function_exists('WC') && WC() && isset(WC()->countries)
        ? (array)WC()->countries->get_shipping_countries() : [];
    $fs_states_us = function_exists('WC') && WC() && isset(WC()->countries)
        ? (array)WC()->countries->get_states('US') : [];
    $fs_asia = [];
    if (function_exists('WC') && WC() && isset(WC()->countries)) {
        $continents = (array)WC()->countries->get_continents();
        foreach ((array)($continents['AS']['countries'] ?? []) as $code) {
            if ($code === 'HK' || !isset($fs_countries[$code])) { continue; }
            // Woo's zone precedence can override continent membership. List
            // only countries that truly resolve to the $69 tier, not every
            // country geographically grouped under Asia.
            $match = WC_Shipping_Zones::get_zone_matching_package([
                'destination'=>['country'=>$code,'state'=>'','postcode'=>'','city'=>''],
                'contents'=>[],
            ]);
            if ($match && method_exists($match, 'get_id') && (int)$match->get_id() === 5) {
                $fs_asia[] = (string)$fs_countries[$code];
            }
        }
        sort($fs_asia, SORT_NATURAL | SORT_FLAG_CASE);
    }
    ?>
    <section class="byl-free__country" id="shipping-by-country" aria-labelledby="byl-free-country-title">
      <p class="byl-free__eyebrow">Shipping destinations</p>
      <h2 id="byl-free-country-title">Free shipping by country &amp; region</h2>
      <p class="byl-free__caption">Different destinations have different shipping rules. See the four current free-shipping tiers below, or look up a specific destination. Actual delivery services depend on your address and cart.</p>

      <div class="byl-free__lookup"
           data-byl-fs-lookup
           data-endpoint="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
           data-nonce="<?php echo esc_attr(wp_create_nonce('byl_v3_fs_lookup')); ?>"
           data-currency="<?php echo esc_attr((string)$state['currency']); ?>">
        <div class="byl-free__lookup-title"><span>Check a destination</span><small>Checking here won't change your delivery address</small></div>
        <div class="byl-free__lookup-fields">
          <label for="byl-free-country-select">Country / region
            <select id="byl-free-country-select" data-byl-fs-country>
              <option value="">Select a country or region</option>
              <?php foreach ($fs_countries as $code => $country_name) : ?>
                <option value="<?php echo esc_attr((string)$code); ?>"><?php echo esc_html((string)$country_name); ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label for="byl-free-state-select" data-byl-fs-state-wrapper hidden>U.S. state / territory
            <select id="byl-free-state-select" data-byl-fs-state>
              <option value="">Select your state</option>
              <?php foreach ($fs_states_us as $code => $state_name) : ?>
                <option value="<?php echo esc_attr((string)$code); ?>"><?php echo esc_html((string)$state_name); ?></option>
              <?php endforeach; ?>
            </select>
          </label>
        </div>
        <p class="byl-free__lookup-result" data-byl-fs-result role="status" aria-live="polite">Choose a destination to see its current free-shipping rule.</p>
      </div>

      <div class="byl-free__tiers">
        <?php foreach ($fs_groups as $group) :
          $zone_id = (int)$group['zone'];
          $supports = $fs_policy && function_exists('byl_v3_fs_zone_free_method')
              && byl_v3_fs_zone_free_method($zone_id,$fs_zones);
          $min_amount = $supports ? $fs_policy::get_persisted_zone_effective_threshold($zone_id) : null;
          $flat_cost = $supports && function_exists('byl_v3_fs_zone_flat_cost')
              ? byl_v3_fs_zone_flat_cost($zone_id,$fs_zones) : null;
        ?>
          <article class="byl-free__tier" data-shipping-zone="<?php echo esc_attr((string)$zone_id); ?>">
            <div class="byl-free__tier-header"><span><?php echo wp_kses_post((string)$group['title']); ?></span>
              <strong><?php echo $min_amount !== null ? esc_html($price($min_amount,true)) : 'Check rates'; ?></strong>
            </div>
            <p class="byl-free__tier-sub"><?php echo $min_amount !== null && (float)$min_amount === 0.0
                ? 'No minimum purchase for eligible Free Shipping'
                : 'Minimum eligible merchandise subtotal'; ?></p>
            <p class="byl-free__tier-region"><?php echo esc_html((string)$group['region']); ?></p>
            <details>
              <summary>Countries &amp; conditions</summary>
              <p><?php echo esc_html((string)$group['detail']); ?></p>
              <?php if ($zone_id === 5 && $fs_asia) : ?>
                <p class="byl-free__country-list"><strong>Included Asian destinations:</strong> <?php echo esc_html(implode(', ', $fs_asia)); ?></p>
              <?php endif; ?>
              <?php if ($flat_cost !== null && $flat_cost > 0) : ?>
                <p>Standard shipping base rate: <?php echo esc_html($price($flat_cost,true)); ?> before any applicable shipping-class charges.</p>
              <?php endif; ?>
            </details>
          </article>
        <?php endforeach; ?>
      </div>
      <p class="byl-free__country-notice"><strong>Other destinations:</strong> Free shipping is not offered for every destination. Check your Cart or Checkout for the delivery methods and final shipping charges available to your address.</p>
    </section>
    <section id="how-free-shipping-works" class="byl-free__how">
      <p class="byl-free__eyebrow">Three easy steps</p>
      <h2>How it works</h2>
      <p class="byl-free__caption">A clearer way to see your shipping savings before checkout.</p>
      <div class="byl-free__steps">
        <article><span>01</span><h3>Fill your cart</h3><p>Pick the products you love. Your cart shows how close you are to your destination's free-shipping minimum.</p></article>
        <article><span>02</span><h3>Reach your goal</h3><p>As soon as your eligible merchandise subtotal meets the minimum, you'll see a celebration.</p></article>
        <article><span>03</span><h3>Choose free delivery</h3><p>If a free-shipping method is offered, select it in Cart or Checkout. Your order total reflects the selected method.</p></article>
      </div>
    </section>
    <section class="byl-free__details" aria-label="Free shipping rules and frequently asked questions">
      <div class="byl-free__fineprint">
        <p class="byl-free__eyebrow">The details</p><h2>Good to know</h2>
        <div><strong>Destination-based thresholds</strong><p>Minimums and delivery methods vary by shipping zone. Your Cart and Checkout use your actual address.</p></div>
        <div><strong>Standard shipping, where available</strong><p>Faster or special shipping options may carry a separate charge, even when free standard shipping is available.</p></div>
        <div><strong>No surprises at checkout</strong><p>Only a free shipping method selected in your order summary reduces its fee to $0.</p></div>
      </div>
      <div class="byl-free__faq" id="free-shipping-faq">
        <p class="byl-free__eyebrow">Help &amp; answers</p><h2>Frequently asked questions</h2>
        <details open><summary>Does free shipping start at $69 everywhere?</summary><p>No. The $69 example applies to one delivery zone. Thresholds vary by destination, and some zones don't offer free shipping. Enter your shipping address in Cart to see the applicable rates.</p></details>
        <details><summary>What happens when I reach my goal?</summary><p>When your qualifying subtotal reaches your zone's minimum and a free-shipping service is eligible, you'll see a congratulations message. Select the Free Shipping method in Cart or Checkout to apply it.</p></details>
        <details><summary>Why do I still see a shipping fee?</summary><p>Your selected shipping method may be a paid service or your destination may have a different threshold. Check your address and available shipping methods in your Cart.</p></details>
        <details><summary>Do discount codes affect shipping eligibility?</summary><p>Eligibility follows the shipping policy shown at Checkout. Your Cart uses the pre-discount merchandise subtotal and your delivery zone; special code-required offers will be identified when applicable.</p></details>
      </div>
    </section>
  </div>
</main>
<?php get_footer(); ?>
