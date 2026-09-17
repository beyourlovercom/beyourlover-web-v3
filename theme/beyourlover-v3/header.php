<?php
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }
$header_profile = byl_v3_current_header_profile();
$features = byl_v3_shell_features($header_profile);
$context = $header_profile === 'landing' ? [] : byl_v3_context_nav();
$cart_count = byl_v3_cart_count();
$primary_menu_id = byl_v3_shell_profile_menu_id($header_profile, false);
$mobile_menu_id = byl_v3_shell_profile_menu_id($header_profile, true) ?: $primary_menu_id;
$fallback_nav = byl_v3_profile_nav_items($header_profile);
$overlay = byl_v3_shell_overlay_enabled($header_profile);
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="byl-skip-link" href="#main-content">Skip to content</a>
<header class="byl-shell-header byl-shell-header--<?php echo esc_attr($header_profile); ?><?php echo $overlay ? ' is-overlay' : ''; ?>" data-byl-shell-header data-byl-header-profile="<?php echo esc_attr($header_profile); ?>">
  <div class="byl-shell-bar byl-container">
    <div class="byl-shell-brand">
      <a href="<?php echo esc_url(home_url('/')); ?>" aria-label="BeYourLover home"><?php echo byl_v3_brand_logo(); ?></a>
    </div>
    <?php if (!empty($features['menu'])): ?>
      <nav class="byl-shell-nav" aria-label="<?php echo esc_attr(ucfirst($header_profile)); ?> navigation" data-byl-desktop-nav>
        <?php if ($primary_menu_id): ?>
          <?php wp_nav_menu(['menu'=>$primary_menu_id,'container'=>false,'menu_id'=>'byl-global-menu','menu_class'=>'byl-shell-menu byl-shell-menu--desktop','depth'=>3,'fallback_cb'=>false]); ?>
        <?php else: ?>
          <ul class="byl-shell-menu byl-shell-menu--desktop byl-shell-menu--fallback">
            <?php foreach ($fallback_nav as $item): ?><li><a href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['label']); ?></a></li><?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </nav>
    <?php endif; ?>
    <div class="byl-shell-actions">
      <?php if (!empty($features['search'])): ?>
        <button class="byl-shell-icon-btn" type="button" data-byl-search-open aria-label="Search">
          <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4 4"/></svg>
        </button>
      <?php endif; ?>
      <?php if (!empty($features['account'])): ?>
        <a class="byl-shell-icon-btn" href="<?php echo esc_url(byl_v3_account_url()); ?>" aria-label="Account">
          <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.25"/><path d="M5.5 19c.7-4 3-6 6.5-6s5.8 2 6.5 6"/></svg>
        </a>
      <?php endif; ?>
      <?php if (!empty($features['cart'])): ?>
        <a class="byl-shell-icon-btn byl-shell-cart" href="<?php echo esc_url(byl_v3_cart_url()); ?>" aria-label="Cart, <?php echo esc_attr((string)$cart_count); ?> items">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h2l1.5 9h9.7l1.7-6.5H7"/><circle cx="9" cy="18.5" r="1"/><circle cx="17" cy="18.5" r="1"/></svg>
          <span class="byl-shell-cart-count"<?php echo $cart_count ? '' : ' hidden'; ?>><?php echo esc_html((string)$cart_count); ?></span>
        </a>
      <?php endif; ?>
      <?php if (!empty($features['cta'])): ?>
        <a class="byl-shell-cta" href="<?php echo esc_url(home_url('/shop/')); ?>">Shop Now</a>
      <?php endif; ?>
      <?php if (!empty($features['menu'])): ?>
        <button class="byl-shell-icon-btn byl-shell-menu-btn" type="button" data-byl-menu-toggle aria-expanded="false" aria-controls="byl-mobile-menu" aria-label="Menu">
          <span></span><span></span>
        </button>
      <?php endif; ?>
    </div>
  </div>
  <?php if (!empty($features['menu'])): ?>
    <div class="byl-mobile-menu" id="byl-mobile-menu" hidden data-byl-mobile-root>
      <nav class="byl-container" aria-label="Mobile navigation">
        <?php if ($mobile_menu_id): ?>
          <?php wp_nav_menu(['menu'=>$mobile_menu_id,'container'=>false,'menu_id'=>'byl-mobile-wordpress-menu','menu_class'=>'byl-mobile-wordpress-menu','depth'=>3,'fallback_cb'=>false]); ?>
        <?php else: ?>
          <ul class="byl-mobile-wordpress-menu byl-mobile-wordpress-menu--fallback">
            <?php foreach ($fallback_nav as $item): ?><li><a href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['label']); ?></a></li><?php endforeach; ?>
          </ul>
        <?php endif; ?>
        <div class="byl-mobile-utility">
          <?php if (!empty($features['account'])): ?><a href="<?php echo esc_url(byl_v3_account_url()); ?>">Account</a><?php endif; ?>
          <?php if (!empty($features['cart'])): ?><a href="<?php echo esc_url(byl_v3_cart_url()); ?>">Cart<?php echo $cart_count ? ' · '.esc_html((string)$cart_count) : ''; ?></a><?php endif; ?>
        </div>
      </nav>
    </div>
  <?php endif; ?>
</header>
<?php if (!empty($context['title']) && !empty($context['items'])): ?>
  <nav class="byl-context-nav" aria-label="<?php echo esc_attr($context['title']); ?> navigation">
    <div class="byl-container byl-context-inner">
      <strong><?php echo esc_html($context['title']); ?></strong>
      <div><?php foreach ($context['items'] as $item): ?><a href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['label']); ?></a><?php endforeach; ?></div>
    </div>
  </nav>
<?php endif; ?>
<?php if (!empty($features['search'])): ?>
<dialog class="byl-search-dialog" data-byl-search-dialog aria-label="Search BeYourLover">
  <div class="byl-search-panel">
    <div class="byl-search-top byl-container">
      <button class="byl-shell-icon-btn" type="button" data-byl-search-close aria-label="Close search">×</button>
    </div>
    <form class="byl-search-form" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
      <label for="byl-global-search">Search BeYourLover</label>
      <div><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4 4"/></svg><input id="byl-global-search" name="s" type="search" autocomplete="off" placeholder="Products, guides and support" value="<?php echo esc_attr(get_search_query()); ?>"></div>
      <p>Press Enter to search the entire site.</p>
    </form>
  </div>
</dialog>
<?php endif; ?>
