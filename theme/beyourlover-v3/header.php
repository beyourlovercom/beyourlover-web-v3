<?php
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }
$context = byl_v3_context_nav();
$cart_count = byl_v3_cart_count();
$primary_menu_id = byl_v3_menu_id('primary');
$mobile_menu_id = byl_v3_menu_id('mobile') ?: $primary_menu_id;
$fallback_nav = byl_v3_fallback_nav_items();
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
<header class="byl-shell-header" data-byl-shell-header>
  <div class="byl-shell-bar byl-container">
    <div class="byl-shell-brand">
      <a href="<?php echo esc_url(home_url('/')); ?>" aria-label="BeYourLover home"><?php echo byl_v3_brand_logo(); ?></a>
    </div>
    <nav class="byl-shell-nav" aria-label="Global navigation">
      <?php if ($primary_menu_id): ?>
        <?php wp_nav_menu(['menu'=>$primary_menu_id,'container'=>false,'menu_id'=>'byl-global-menu','menu_class'=>'byl-shell-menu byl-shell-menu--desktop','depth'=>3,'fallback_cb'=>false]); ?>
      <?php else: ?>
        <ul class="byl-shell-menu byl-shell-menu--desktop byl-shell-menu--fallback"><?php foreach ($fallback_nav as $item): ?><li><a href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['label']); ?></a></li><?php endforeach; ?></ul>
      <?php endif; ?>
    </nav>
    <div class="byl-shell-actions">
      <button class="byl-shell-icon-btn" type="button" data-byl-search-open aria-label="Search">
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4 4"/></svg>
      </button>
      <a class="byl-shell-icon-btn" href="<?php echo esc_url(byl_v3_account_url()); ?>" aria-label="Account">
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.25"/><path d="M5.5 19c.7-4 3-6 6.5-6s5.8 2 6.5 6"/></svg>
      </a>
      <a class="byl-shell-icon-btn byl-shell-cart" href="<?php echo esc_url(byl_v3_cart_url()); ?>" aria-label="Cart, <?php echo esc_attr((string)$cart_count); ?> items">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h2l1.5 9h9.7l1.7-6.5H7"/><circle cx="9" cy="18.5" r="1"/><circle cx="17" cy="18.5" r="1"/></svg>
        <span class="byl-shell-cart-count"<?php echo $cart_count ? '' : ' hidden'; ?>><?php echo esc_html((string)$cart_count); ?></span>
      </a>
      <button class="byl-shell-icon-btn byl-shell-menu-btn" type="button" data-byl-menu-toggle aria-expanded="false" aria-controls="byl-mobile-menu" aria-label="Menu">
        <span></span><span></span>
      </button>
    </div>
  </div>
  <div class="byl-mobile-menu" id="byl-mobile-menu" hidden>
    <nav class="byl-container" aria-label="Mobile navigation">
      <?php if ($mobile_menu_id): ?>
        <?php wp_nav_menu(['menu'=>$mobile_menu_id,'container'=>false,'menu_id'=>'byl-mobile-wordpress-menu','menu_class'=>'byl-mobile-wordpress-menu','depth'=>3,'fallback_cb'=>false]); ?>
      <?php else: ?>
        <ul class="byl-mobile-wordpress-menu byl-mobile-wordpress-menu--fallback"><?php foreach ($fallback_nav as $item): ?><li><a href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['label']); ?></a></li><?php endforeach; ?></ul>
      <?php endif; ?>
      <div class="byl-mobile-utility">
        <a href="<?php echo esc_url(byl_v3_account_url()); ?>">Account</a>
        <a href="<?php echo esc_url(byl_v3_cart_url()); ?>">Cart<?php echo $cart_count ? ' 路 '.esc_html((string)$cart_count) : ''; ?></a>
      </div>
    </nav>
  </div>
</header>
<?php if (!empty($context['title']) && !empty($context['items'])): ?>
  <nav class="byl-context-nav" aria-label="<?php echo esc_attr($context['title']); ?> navigation">
    <div class="byl-container byl-context-inner">
      <strong><?php echo esc_html($context['title']); ?></strong>
      <div><?php foreach ($context['items'] as $item): ?><a href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['label']); ?></a><?php endforeach; ?></div>
    </div>
  </nav>
<?php endif; ?>
<dialog class="byl-search-dialog" data-byl-search-dialog aria-label="Search BeYourLover">
  <div class="byl-search-panel">
    <div class="byl-search-top byl-container">
      <button class="byl-shell-icon-btn" type="button" data-byl-search-close aria-label="Close search">脳</button>
    </div>
    <form class="byl-search-form" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
      <label for="byl-global-search">Search BeYourLover</label>
      <div><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4 4"/></svg><input id="byl-global-search" name="s" type="search" autocomplete="off" placeholder="Products, guides and support" value="<?php echo esc_attr(get_search_query()); ?>"></div>
      <p>Press Enter to search the entire site.</p>
    </form>
  </div>
</dialog>
