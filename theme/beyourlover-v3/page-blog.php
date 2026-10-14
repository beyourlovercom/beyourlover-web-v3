<?php
/**
 * Blog WWW Parity V1 — active Dev /blog/ route for Owner UAT.
 * The dedicated Blog template replaces the legacy Page 126 output on Dev.
 * Uses real published WordPress posts/terms, never mutates DB, orders or mail.
 */
declare(strict_types=1);
defined('ABSPATH') || exit;
add_filter('body_class', static function ($classes) {
    $classes[] = 'byl-blog-parity-v1';
    return $classes;
});
add_action('wp_enqueue_scripts', static function (): void {
    $uri = get_stylesheet_directory_uri();
    $dir = get_stylesheet_directory();
    foreach (['css' => 'assets/css/blog-parity-v1.css', 'js' => 'assets/js/blog-parity-v1.js'] as $kind => $name) {
        $version = is_file($dir.'/'.$name) ? (string) filemtime($dir.'/'.$name) : '0.1.0';
        if ($kind === 'css') {
            wp_enqueue_style('byl-blog-parity-v1', $uri.'/'.$name, ['byl-v3-shell'], $version);
        } else {
            wp_enqueue_script('byl-blog-parity-v1', $uri.'/'.$name, [], $version, true);
            wp_script_add_data('byl-blog-parity-v1', 'strategy', 'defer');
        }
    }
}, 20);
$specs = [
    ['slug'=>'sex-toy-guides','name'=>'SEX TOY GUIDES','label'=>'SEX TOY GUIDES','icon'=>'2026/09/08032619/blog-class-icon01.webp'],
    ['slug'=>'sex-positions','name'=>'SEX POSITIONS','label'=>'SEX POSITIONS','icon'=>'2026/09/08032625/blog-class-icon02.webp'],
    ['slug'=>'sex-tips-advice','name'=>'SEX TIPS & ADVICE','label'=>'SEX TIPS & ADVICE','icon'=>'2026/09/08032632/blog-class-icon03.webp'],
    ['slug'=>'sexual-wellness','name'=>'SEXUAL WELLNESS','label'=>'SEXUAL WELLNESS','icon'=>'2026/09/08032638/blog-class-icon04.webp'],
    ['slug'=>'relationships','name'=>'RELATIONSHIPS','label'=>'RELATIONSHIPS','icon'=>'2026/09/08032644/blog-class-icon05.webp'],
    ['slug'=>'news','name'=>'NEWS','label'=>'NEWS','icon'=>'2026/09/08032650/blog-class-icon06.webp'],
];
$icon_root = 'https://img.beyourlover.com/';
$all_posts = [];
foreach ($specs as &$spec) {
    $term = get_term_by('slug', $spec['slug'], 'category');
    $spec['url'] = $term instanceof WP_Term && !is_wp_error($term)
        ? get_term_link($term) : home_url('/category/'.rawurlencode($spec['slug']).'/');
    $spec['posts'] = [];
    if (!$term instanceof WP_Term || is_wp_error($term)) { continue; }
    $query = new WP_Query([
        'post_type'=>'post','post_status'=>'publish','posts_per_page'=>8,
        'cat'=>(int) $term->term_id,'orderby'=>'date','order'=>'DESC',
        'ignore_sticky_posts'=>true,'no_found_rows'=>true,
    ]);
    foreach ($query->posts as $article) {
        $post_id = (int) $article->ID;
        $image = (string) get_the_post_thumbnail_url($post_id, 'medium_large');
        $entry = [
            'id'=>$post_id,
            'title'=>html_entity_decode((string)get_the_title($post_id), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'url'=>get_permalink($post_id),
            'image'=>$image,
            'date'=>get_the_date('Y-m-d', $post_id),
            'excerpt'=>wp_trim_words(wp_strip_all_tags((string) get_the_excerpt($post_id)), 32),
            'category'=>$spec['name'],
            'slug'=>$spec['slug'],
            'readTime'=>max(1, (int) ceil(str_word_count(wp_strip_all_tags($article->post_content))/220)),
        ];
        $spec['posts'][] = $entry;
        if (!isset($all_posts[$post_id])) { $all_posts[$post_id] = $entry; }
    }
    wp_reset_postdata();
}
unset($spec);
$featured = get_posts(['post_type'=>'post','post_status'=>'publish','posts_per_page'=>3,'orderby'=>'date','order'=>'DESC','ignore_sticky_posts'=>true]);
$hero_posts = [];
foreach ($featured as $post) {
    $id = (int) $post->ID;
    $hero_posts[] = ['title'=>html_entity_decode((string)get_the_title($id), ENT_QUOTES | ENT_HTML5, 'UTF-8'),'url'=>get_permalink($id),
        'image'=>(string) get_the_post_thumbnail_url($id, 'full')];
}
// Visual UAT uses a current public WWW campaign reference only as a temporary
// fallback. Published content in production must remain CMS-driven.
if (empty($hero_posts)) {
    $hero_posts[] = ['title'=>'Lover’s Journal','url'=>home_url('/blog/'),'image'=>''];
}
get_header();
?>
<main id="main-content" class="byl-blogv1" data-byl-blogv1>
  <div class="byl-blogv1-announcement">Cute, Original Toys Made With ❤️ &nbsp;·&nbsp; Fast Worldwide Discreet Shipping</div>
  <div class="byl-blogv1-headingbar">
    <span>BeYourLover Blog</span><nav aria-label="Blog sections"><a href="#byl-blogv1-journal">Journal</a><a href="#byl-blogv1-explore">Explore</a></nav>
  </div>
  <section class="byl-blogv1-hero" aria-label="Featured stories">
    <div class="byl-blogv1-hero-track" data-hero-track>
    <?php foreach ($hero_posts as $k=>$h): ?>
      <article class="byl-blogv1-hero-item<?php echo $k === 0 ? ' is-active' : ''; ?>" data-hero-slide="<?php echo esc_attr((string)$k); ?>">
        <a href="<?php echo esc_url($h['url']); ?>">
          <?php if ($h['image'] !== ''): ?><img src="<?php echo esc_url($h['image']); ?>" alt="<?php echo esc_attr($h['title']); ?>" <?php echo $k === 0 ? 'fetchpriority="high"' : 'loading="lazy"'; ?>><?php endif; ?>
          <span class="byl-blogv1-hero-title"><?php echo esc_html($h['title']); ?></span>
        </a>
      </article>
    <?php endforeach; ?>
    </div>
    <button type="button" data-hero-move="-1" class="byl-blogv1-hero-arrow is-prev" aria-label="Previous story">‹</button>
    <button type="button" data-hero-move="1" class="byl-blogv1-hero-arrow is-next" aria-label="Next story">›</button>
    <div class="byl-blogv1-hero-dots" aria-label="Featured story selection"><?php foreach ($hero_posts as $k=>$h): ?><button type="button" data-hero-select="<?php echo esc_attr((string)$k); ?>" aria-label="Show story <?php echo (int)$k+1; ?>" aria-current="<?php echo $k===0?'true':'false'; ?>"></button><?php endforeach; ?></div>
  </section>
  <section class="byl-blogv1-journal" id="byl-blogv1-journal">
    <h1>LOVER'S JOURNAL</h1>
    <div class="byl-blogv1-categories">
      <?php foreach ($specs as $c): ?>
      <a class="byl-blogv1-category" href="<?php echo esc_url($c['url']); ?>" data-category-link="<?php echo esc_attr($c['slug']); ?>">
        <img src="<?php echo esc_url($icon_root.$c['icon']); ?>" alt="" loading="lazy">
        <strong><?php echo esc_html($c['label']); ?></strong>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <div class="byl-blogv1-sections">
    <?php foreach ($specs as $c): ?>
    <section class="byl-blogv1-section">
      <header><h2><?php echo esc_html($c['name']); ?></h2><span class="byl-blogv1-arrows"><a href="<?php echo esc_url($c['url']); ?>" class="byl-blogv1-more">MORE ↗</a><button type="button" data-scroll-cat="<?php echo esc_attr($c['slug']); ?>" data-direction="-1" aria-label="Previous <?php echo esc_attr($c['name']); ?> articles">‹</button><button type="button" data-scroll-cat="<?php echo esc_attr($c['slug']); ?>" data-direction="1" aria-label="Next <?php echo esc_attr($c['name']); ?> articles">›</button></span></header>
      <div class="byl-blogv1-scroll" data-cat-track="<?php echo esc_attr($c['slug']); ?>">
        <?php if (!$c['posts']): ?><p>No published articles available yet.</p><?php endif; ?>
        <?php foreach ($c['posts'] as $item): ?>
          <a class="byl-blogv1-slide" href="<?php echo esc_url($item['url']); ?>">
            <span class="byl-blogv1-image"><?php if ($item['image']): ?><img loading="lazy" src="<?php echo esc_url($item['image']); ?>" alt="<?php echo esc_attr($item['title']); ?>"><?php endif; ?></span>
            <strong><?php echo esc_html($item['title']); ?></strong>
            <small><?php echo esc_html($item['date']); ?> · <?php echo (int)$item['readTime']; ?> min read</small>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endforeach; ?>
  </div>
  <section class="byl-blogv1-explore" id="byl-blogv1-explore">
    <span class="byl-blogv1-eyebrow">DISCOVER MORE</span><h2>Explore Articles</h2>
    <p>Search and browse published BeYourLover stories.</p>
    <form class="byl-blogv1-filters" role="search" data-explore-search>
      <input type="search" name="search" placeholder="Search article titles..." aria-label="Search articles">
      <select name="category" aria-label="Filter articles by category"><option value="all">All categories</option><?php foreach ($specs as $c): ?><option value="<?php echo esc_attr($c['slug']); ?>"><?php echo esc_html($c['name']); ?></option><?php endforeach; ?></select>
      <select name="sort" aria-label="Sort articles"><option value="new">Newest</option><option value="old">Oldest</option><option value="title">Title A–Z</option><option value="title-desc">Title Z–A</option><option value="category">Category A–Z</option></select>
      <button type="button" data-view="cards" aria-pressed="true">▦ Cards</button><button type="button" data-view="list" aria-pressed="false">☷ List</button>
      <div class="byl-blogv1-column-anchor"><button type="button" data-col-open aria-expanded="false">⚙ Columns</button><div data-col-menu hidden></div></div>
    </form>
    <p class="byl-blogv1-status" role="status" data-result-count></p>
    <div data-explore-results></div>
    <div class="byl-blogv1-pagination"><label>Per page <select data-page-size><option value="6">6</option><option value="12">12</option><option value="24">24</option><option value="all">All</option></select></label><div data-pages></div></div>
  </section>
  <section class="byl-blogv1-club"><h2>STAY IN THE LOOP ♡</h2><p>Get new stories and discoveries. Newsletter subscriptions remain on the existing, dedicated page.</p><a href="<?php echo esc_url(home_url('/newsletter/')); ?>">Visit Newsletter →</a></section>
  <section class="byl-blogv1-about"><h2>Welcome to the BeYourLover Blog</h2><p>Explore helpful guides, thoughtful relationship advice and the latest stories. Posts and categories are served directly from WordPress.</p></section>
  <div class="byl-blogv1-dialog" data-dialog-wrap hidden><div class="byl-blogv1-backdrop" data-modal-close></div><section role="dialog" aria-modal="true" aria-labelledby="byl-blogv1-dialog-title" data-modal-content><button type="button" data-modal-close aria-label="Close article preview">×</button><div data-dialog-body></div></section></div>
  <script type="application/json" id="byl-blogv1-data"><?php echo wp_json_encode(array_values($all_posts), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
  <div class="byl-blogv1-preview-note">B1–B4 Dev Owner UAT · real published posts · no checkout/order changes</div>
</main>
<?php get_footer(); ?>
