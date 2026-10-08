<?php
/**
 * Focused Checkout V1 Header — no global shop navigation during purchase.
 * Brand / support / back-to-cart are legitimate exits, not conversion traps.
 */
declare(strict_types=1);
defined('ABSPATH') || exit;
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class('byl-secure-checkout-shell'); ?>>
<?php wp_body_open(); ?>
<a class="byl-skip-link" href="#main-content"><?php esc_html_e('Skip to content', 'beyourlover-v3'); ?></a>
<header class="byl-secure-header" role="banner">
  <div class="byl-secure-header__inner">
    <a class="byl-secure-brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php esc_attr_e('BeYourLover home', 'beyourlover-v3'); ?>"><?php echo byl_v3_brand_logo(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
    <div class="byl-secure-header__assurance">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
      <span><?php esc_html_e('Secure Checkout', 'beyourlover-v3'); ?></span>
    </div>
    <div class="byl-secure-header__links">
      <a class="byl-secure-header__help" href="<?php echo esc_url(home_url('/customer-help/')); ?>"><?php esc_html_e('Need help?', 'beyourlover-v3'); ?></a>
      <a class="byl-secure-header__back" href="<?php echo esc_url(byl_v3_cart_url()); ?>">← <?php esc_html_e('Back to cart', 'beyourlover-v3'); ?></a>
    </div>
  </div>
</header>
