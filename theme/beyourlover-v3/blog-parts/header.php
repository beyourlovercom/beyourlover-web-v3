<?php
/** Standalone Blog V1 header. Intentionally does not use Shop shell/header. */
declare(strict_types=1);
defined('ABSPATH') || exit;
$blog_nav = [
    ['label'=>'Shop','url'=>home_url('/shop/')],
    ['label'=>'Sex Educations','url'=>home_url('/sex-educations/')],
    ['label'=>'Sex Position','url'=>home_url('/sex-positions/')],
    ['label'=>'Tips','url'=>home_url('/sex-tips-advice/')],
    ['label'=>'Guide','url'=>home_url('/sex-toy-guides/')],
    ['label'=>'Sexual Wellness','url'=>home_url('/sexual-wellness/')],
];
$blog_logo = 'https://img.beyourlover.com/2025/06/21035821/logo-slogan-red-1-scaled-1.webp';
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class('byl-blog-standalone'); ?>>
<?php wp_body_open(); ?>
<a class="byl-blogv1-skip" href="#main-content">Skip to blog content</a>
<div class="byl-blogv1-topline">Cute, Original Toys Made With ❤️ &nbsp; · &nbsp; Fast Worldwide Discreet Shipping</div>
<header class="byl-blogv1-site-header" data-blog-header>
    <button class="byl-blogv1-nav-icon byl-blogv1-menu-toggle" type="button" data-blog-menu-toggle
      aria-expanded="false" aria-controls="byl-blog-mobile-menu" aria-label="Open Blog menu">
      <span></span><span></span><span></span>
    </button>
    <nav class="byl-blogv1-header-links byl-blogv1-header-links--left" aria-label="Blog primary navigation">
      <?php foreach (array_slice($blog_nav, 0, 3) as $item): ?>
      <a href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['label']); ?></a>
      <?php endforeach; ?>
    </nav>
    <a class="byl-blogv1-site-logo" href="<?php echo esc_url(home_url('/blog/')); ?>" aria-label="BeYourLover Blog home">
      <img src="<?php echo esc_url($blog_logo); ?>" alt="BeYourLover" width="240" height="41">
    </a>
    <nav class="byl-blogv1-header-links byl-blogv1-header-links--right" aria-label="More Blog navigation">
      <?php foreach (array_slice($blog_nav, 3) as $item): ?>
      <a href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['label']); ?></a>
      <?php endforeach; ?>
    </nav>
    <button class="byl-blogv1-nav-icon byl-blogv1-search-toggle" type="button" data-blog-search-toggle
      aria-expanded="false" aria-controls="byl-blog-header-search" aria-label="Search Blog articles">
      <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="10.7" cy="10.7" r="6.5"/><path d="m15.5 15.5 5.2 5.2"/></svg>
    </button>
</header>
<nav class="byl-blogv1-mobile-menu" id="byl-blog-mobile-menu" aria-label="Blog mobile navigation" hidden>
  <?php foreach ($blog_nav as $item): ?><a href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['label']); ?></a><?php endforeach; ?>
  <a href="<?php echo esc_url(home_url('/blog/')); ?>">BeYourLover Blog</a>
</nav>
<div class="byl-blogv1-header-search" id="byl-blog-header-search" hidden>
  <label for="byl-blog-header-query">Search blog posts</label>
  <input type="search" id="byl-blog-header-query" data-blog-header-query placeholder="Search titles or categories...">
  <button type="button" data-blog-header-submit>Search</button>
  <button type="button" data-blog-search-close aria-label="Close Blog search">×</button>
</div>
