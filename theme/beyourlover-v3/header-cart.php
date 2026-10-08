<?php
/** Cart-only focused header. WP/Woo remain responsible for session and cart state. */
declare(strict_types=1);
defined('ABSPATH') || exit;
$count = byl_v3_cart_count();
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class('byl-focus-cart-shell'); ?>>
<?php wp_body_open(); ?>
<a class="byl-skip-link" href="#main-content"><?php esc_html_e('Skip to content', 'beyourlover-v3'); ?></a>
<header class="byl-focus-header" role="banner">
  <div class="byl-focus-header__inner">
    <a class="byl-focus-logo" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php esc_attr_e('BeYourLover home', 'beyourlover-v3'); ?>"><?php echo byl_v3_brand_logo(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
    <div class="byl-focus-security">
      <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
      <span><strong><?php esc_html_e('Secure shopping', 'beyourlover-v3'); ?></strong><small><?php esc_html_e('Discreet packaging · Your privacy matters', 'beyourlover-v3'); ?></small></span>
    </div>
    <div class="byl-focus-actions">
      <a class="byl-focus-help" href="<?php echo esc_url(home_url('/customer-help/')); ?>"><span aria-hidden="true">?</span><?php esc_html_e('Need help?', 'beyourlover-v3'); ?></a>
      <a class="byl-focus-icon" href="<?php echo esc_url(byl_v3_account_url()); ?>" aria-label="<?php esc_attr_e('My account', 'beyourlover-v3'); ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-5 3-7 8-7s8 2 8 7"/></svg></a>
      <a class="byl-focus-icon byl-focus-cart-icon" href="<?php echo esc_url(byl_v3_cart_url()); ?>" aria-label="<?php echo esc_attr(sprintf(__('Cart, %d items', 'beyourlover-v3'), $count)); ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h2l1.5 9h9.8l2-7H7"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg><?php if ($count) : ?><span class="byl-focus-cart-badge"><?php echo esc_html((string)$count); ?></span><?php endif; ?></a>
    </div>
  </div>
</header>
