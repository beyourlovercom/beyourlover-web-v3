<?php
/**
 * BYL V3 — WWW-style editorial endmatter for a single blog Post.
 *
 * Editorial byline is the public WWW site profile (Liz B.), not WP account
 * author metadata. The public WWW template shows the same vetted card for
 * multiple posts; this file is the one theme-owned place to review/update it.
 * Related records are retrieved read-only from local published WordPress posts.
 */
declare(strict_types=1);
defined('ABSPATH') || exit;

$byl_editorial_bio = 'Liz has always been passionate about helping people with intimacy, relationships, and personal well-being. She finds joy in creating a judgment-free space where her readers can feel informed, comfortable, and confident in their own skin. Professionally, Liz has been a writer for over 12 years. In her free time, you’ll find her in a martial arts class or swimming lesson. She is also on her way to becoming a wellness instructor. When she is not on the move, she enjoys reading or listening to self-help, romance, and sci-fi books, or learning new skills just for fun.';

$byl_related_args = [
    'post_type' => 'post',
    'post_status' => 'publish',
    'posts_per_page' => 5,
    'post__not_in' => [(int) get_the_ID()],
    'orderby' => 'date',
    'order' => 'DESC',
    'ignore_sticky_posts' => true,
    'no_found_rows' => true,
];
$byl_cat_ids = [];
if (is_array($byl_categories ?? null)) {
    foreach ($byl_categories as $byl_category) {
        if ($byl_category instanceof WP_Term && (int) $byl_category->term_id > 0) {
            $byl_cat_ids[] = (int) $byl_category->term_id;
        }
    }
}
if ($byl_cat_ids) {
    $byl_related_args['category__in'] = $byl_cat_ids;
}
$byl_related_posts = get_posts($byl_related_args);
if (!is_array($byl_related_posts)) {
    $byl_related_posts = [];
}
if (count($byl_related_posts) < 5) {
    $byl_existing_ids = [(int) get_the_ID()];
    foreach ($byl_related_posts as $byl_related_post) {
        if ($byl_related_post instanceof WP_Post) {
            $byl_existing_ids[] = (int) $byl_related_post->ID;
        }
    }
    $byl_extra = get_posts([
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => 5 - count($byl_related_posts),
        'post__not_in' => $byl_existing_ids,
        'orderby' => 'date',
        'order' => 'DESC',
        'ignore_sticky_posts' => true,
        'no_found_rows' => true,
    ]);
    if (is_array($byl_extra)) {
        $byl_related_posts = array_merge($byl_related_posts, $byl_extra);
    }
}
$byl_related_posts = array_slice($byl_related_posts, 0, 5);
?>

<section class="byl-blog-author" aria-labelledby="byl-blog-editorial-name">
  <img class="byl-blog-author__avatar"
       src="https://img.beyourlover.com/2025/03/20054038/Bella-profile-scaled.webp"
       alt="<?php esc_attr_e('Liz B. editorial profile', 'beyourlover-v3'); ?>"
       loading="lazy" width="100" height="100"/>
  <div class="byl-blog-author__details">
    <p class="byl-blog-author__role"><?php esc_html_e('Sexologist', 'beyourlover-v3'); ?></p>
    <h2 class="byl-blog-author__name" id="byl-blog-editorial-name">Liz B.</h2>
    <p class="byl-blog-author__bio"><?php echo esc_html($byl_editorial_bio); ?></p>
    <nav class="byl-blog-author__social" aria-label="<?php esc_attr_e('Editorial social profiles', 'beyourlover-v3'); ?>">
      <a href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php esc_attr_e('BeYourLover website', 'beyourlover-v3'); ?>" title="<?php esc_attr_e('Website', 'beyourlover-v3'); ?>">◎</a>
      <a href="https://www.facebook.com/beyourlovercom/" aria-label="Facebook" title="Facebook">f</a>
      <a href="https://www.youtube.com/beyourlovercom" aria-label="YouTube" title="YouTube">▶</a>
      <a href="https://www.instagram.com/beyourlovertoys/" aria-label="Instagram" title="Instagram">▣</a>
    </nav>
  </div>
</section>

<?php if ($byl_previous instanceof WP_Post || $byl_next instanceof WP_Post) : ?>
  <nav class="byl-post-single__navigation" aria-label="<?php esc_attr_e('Previous and next articles', 'beyourlover-v3'); ?>">
    <?php if ($byl_previous instanceof WP_Post) : ?>
      <a class="byl-post-single__nav-item byl-post-single__nav-item--previous" href="<?php echo esc_url(get_permalink($byl_previous)); ?>">
        <span class="byl-post-single__nav-arrow" aria-hidden="true">←</span>
        <span class="byl-post-single__nav-copy">
          <span class="byl-post-single__nav-label"><?php esc_html_e('PREVIOUS', 'beyourlover-v3'); ?></span>
          <strong><?php echo esc_html(get_the_title($byl_previous)); ?></strong>
        </span>
      </a>
    <?php endif; ?>
    <?php if ($byl_next instanceof WP_Post) : ?>
      <a class="byl-post-single__nav-item byl-post-single__nav-item--next" href="<?php echo esc_url(get_permalink($byl_next)); ?>">
        <span class="byl-post-single__nav-copy">
          <span class="byl-post-single__nav-label"><?php esc_html_e('NEXT', 'beyourlover-v3'); ?></span>
          <strong><?php echo esc_html(get_the_title($byl_next)); ?></strong>
        </span>
        <span class="byl-post-single__nav-arrow" aria-hidden="true">→</span>
      </a>
    <?php endif; ?>
  </nav>
<?php endif; ?>

<?php if ($byl_related_posts) : ?>
  <section class="byl-blog-related" aria-labelledby="byl-blog-related-title">
    <h2 class="byl-blog-related__title" id="byl-blog-related-title"><?php esc_html_e('Related Content', 'beyourlover-v3'); ?></h2>
    <ul class="byl-blog-related__list">
      <?php foreach ($byl_related_posts as $byl_related_post) : ?>
        <?php if (!($byl_related_post instanceof WP_Post)) { continue; } ?>
        <li class="byl-blog-related__item">
          <a href="<?php echo esc_url(get_permalink($byl_related_post)); ?>">
            <span class="byl-blog-related__file-icon" aria-hidden="true">≡</span>
            <span class="byl-blog-related__link-title"><?php echo esc_html(get_the_title($byl_related_post)); ?></span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>
<?php endif; ?>
