<?php
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }
/**
 * BeYourLover Theme OS T2.1: ordered, eager runtime bootstrap.
 * Each require_once is identical to the original functions.php sequence.
 * Do not lazy-load or change registration order of Woo hooks.
 */
require_once get_stylesheet_directory().'/inc/components.php';
require_once get_stylesheet_directory().'/inc/shell-profiles.php';
require_once get_stylesheet_directory().'/inc/homepage.php';
require_once get_stylesheet_directory().'/inc/archive.php';
require_once get_stylesheet_directory().'/inc/product.php';
require_once get_stylesheet_directory().'/inc/product-modules.php';
require_once get_stylesheet_directory().'/inc/checkout.php';
// Order Pay: read-only address confirmation after Woo has authorized the customer.
require_once get_stylesheet_directory().'/inc/order-pay-address.php';
// Isolated P7.1 order-received template, without modifying G3 payment JS.
require_once get_stylesheet_directory().'/inc/thank-you.php';
require_once get_stylesheet_directory().'/inc/cart-experience.php';
require_once get_stylesheet_directory().'/inc/cart-drawer.php';
// Compact persistent Woo Notice C1/C2/C3: Dev-only, GET-only explicit opt-in.
require_once get_stylesheet_directory().'/inc/notification-compact-cart.php';
require_once get_stylesheet_directory().'/inc/notification-compact-checkout.php';
require_once get_stylesheet_directory().'/inc/notification-compact-orderpay.php';
