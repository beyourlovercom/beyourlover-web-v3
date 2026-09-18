<?php
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

function byl_v3_product_module_config(WC_Product $product): array {
    $slug = (string)get_post_field('post_name', $product->get_id());
    $configs = [
        'naughty-clock-pink' => [
            'm01' => [
                'eyebrow' => 'Naughty Clock',
                'headline' => 'Every Second Has A Secret',
                'accent' => 'A Secret',
                'copy' => 'An everyday alarm clock with pleasure you’ll want to wake up to.',
                'media' => 'https://www.beyourlover.com/wp-content/uploads/2026/02/naughty-clock-pink-banner.png',
                'alt' => 'Naughty Clock Pink',
            ],
            'm02' => [
                'title' => 'Get the highlights.',
                'intro' => 'Three reasons Naughty Clock feels different.',
                'items' => [
                    [
                        'eyebrow' => 'Naughty Clock Pink',
                        'title' => '2-in-1 Design',
                        'copy' => 'A working alarm clock on the outside. Detachable suction and vibration functions for private moments.',
                        'media' => 'https://www.beyourlover.com/wp-content/uploads/2026/02/naughty-clock-pink-selling-points-01.gif',
                        'alt' => '2-in-1 Design',
                    ],
                    [
                        'eyebrow' => 'Hidden in plain sight',
                        'title' => 'Cute, Yet Discreet',
                        'copy' => 'Designed to live naturally on your nightstand without giving away what it can really do.',
                        'media' => 'https://www.beyourlover.com/wp-content/uploads/2026/02/naughty-clock-pink-selling-points-02.png',
                        'alt' => 'Cute, Yet Discreet',
                    ],
                    [
                        'eyebrow' => 'Made to surprise',
                        'title' => 'A Gift for Special Moments',
                        'copy' => 'A playful, polished gift idea for anniversaries, Valentine’s Day, and nights worth remembering.',
                        'media' => 'https://www.beyourlover.com/wp-content/uploads/2026/02/naughty-clock-pink-selling-points-03.png',
                        'alt' => 'A Gift for Special Moments',
                    ],
                ],
            ],
            'm03' => [
                'title' => 'See It in Motion.',
                'copy' => 'A cinematic product moment focused on movement, mood and product feel.',
                'video' => 'https://img.beyourlover.com/2026/07/29060336/naughty-clock-second-video.mp4',
            ],
            'm05' => [
                'eyebrow' => 'Feature Story',
                'title' => 'More depth. More control.',
                'copy' => 'One story per viewport. Real product media, real product claims, and enough space for each idea to land.',
                'items' => [
                    ['kicker'=>'01 · Sonic stimulation','title'=>'Deeper Sensations','copy'=>'Powered by a high-speed 6000 RPM ultra-pulse engine that sends targeted sonic waves to stimulate thousands of nerve endings, creating more intense sensations.','media'=>'https://www.beyourlover.com/wp-content/uploads/2026/02/naughty-clock-pink-gif-02.gif','alt'=>'Naughty Clock Pink deeper sensations'],
                    ['kicker'=>'02 · Precision motor','title'=>'Powerful Vibrations','copy'=>'Runs on a precision tungsten steel motor that exceeds 185 vibrations per second, delivering a fast, thrilling, and intimate experience that moves at your preferred pace.','media'=>'https://www.beyourlover.com/wp-content/uploads/2026/02/naughty-clock-pink-gif-03.gif','alt'=>'Naughty Clock Pink powerful vibrations'],
                    ['kicker'=>'03 · Two sensations','title'=>'Dual Stimulation','copy'=>'Features external suction and internal vibration, allowing you to use each independently or together to fully customize your experience.','media'=>'https://www.beyourlover.com/wp-content/uploads/2026/02/naughty-clock-pink-gif-01.gif','alt'=>'Naughty Clock Pink dual stimulation'],
                ],
            ],
            'm06' => [
                'title' => 'Discover More.',
                'copy' => 'Explore more verified Naughty Clock features.',
                'items' => [
                    ['title'=>'100% Discreet','short'=>'Discreet payment and packaging keep what is inside private.','detail'=>'Both payment and packaging are discreet, so there’s no need to worry about anyone knowing what’s inside the box.','media'=>'https://cdn.shopify.com/s/files/1/0606/8423/9010/files/naughty_clock_pink_more_features_06.jpg?v=1769152391'],
                    ['title'=>'3 Playful Colors','short'=>'Choose the color that best suits your nightstand.','detail'=>'Choose the color that best suits your nightstand. Available in cute pink-yellow, green, or pink.','media'=>'https://cdn.shopify.com/s/files/1/0606/8423/9010/files/naughty_clock_pink_more_features_04.webp?v=1766383071'],
                    ['title'=>'App Controlled','short'=>'Control the toy in real time through the app.','detail'=>'Whether you’re playing solo or with a partner, control your toys in real time through the app even when you’re miles apart.','media'=>'https://cdn.shopify.com/s/files/1/0606/8423/9010/files/naughty_clock_pink_more_features_05.webp?v=1766383071'],
                    ['title'=>'Whisper-Quiet','short'=>'Designed to keep sound levels under 55 dB.','detail'=>'Designed with advanced noise-reduction technology to keep sound levels under 55 dB, so your intimate moments stay private.','media'=>'https://cdn.shopify.com/s/files/1/0606/8423/9010/files/naughty_clock_pink_more_features_03.webp?v=1766383071'],
                    ['title'=>'Customizable Modes','short'=>'8 suction modes and 9 vibration modes.','detail'=>'Features 8 suction modes and 9 vibration modes. Mix and match based on your mood, and take control of your pleasure your way.','media'=>'https://cdn.shopify.com/s/files/1/0606/8423/9010/files/naughty_clock_pink_more_features_01.webp?v=1766383071'],
                    ['title'=>'IPX6 Waterproof','short'=>'Detachable suction and vibrator parts are IPX6 waterproof.','detail'=>'The detachable parts are IPX6 waterproof, making them easy to rinse and clean. The main device itself is not waterproof.','media'=>'https://cdn.shopify.com/s/files/1/0606/8423/9010/files/naughty_clock_pink_more_features_02.webp?v=1766383071'],
                ],
            ],
        ],
    ];
    return (array)apply_filters('byl_v3_product_module_config', $configs[$slug] ?? [], $product);
}

function byl_v3_product_module_enabled(array $config, string $key): bool {
    return isset($config[$key]) && is_array($config[$key]) && $config[$key] !== [];
}
function byl_v3_render_product_module_m01(array $module): void {
    $headline = trim((string)($module['headline'] ?? ''));
    $accent = trim((string)($module['accent'] ?? ''));
    if ($headline === '') { return; }
    $headline_html = esc_html($headline);
    if ($accent !== '' && str_contains($headline, $accent)) {
        $headline_html = str_replace(esc_html($accent), '<span class="byl-pdp-m01-accent">'.esc_html($accent).'</span>', $headline_html);
    }
?>
<section class="byl-pdp-module byl-pdp-m01" aria-labelledby="byl-pdp-m01-title">
  <div class="byl-pdp-m01-copy">
    <?php if (!empty($module['eyebrow'])): ?><div class="byl-pdp-m01-eyebrow"><?php echo esc_html((string)$module['eyebrow']); ?></div><?php endif; ?>
    <h2 id="byl-pdp-m01-title"><?php echo wp_kses($headline_html, ['span'=>['class'=>true]]); ?></h2>
    <?php if (!empty($module['copy'])): ?><p><?php echo esc_html((string)$module['copy']); ?></p><?php endif; ?>
  </div>
  <?php if (!empty($module['media'])): ?>
  <div class="byl-pdp-m01-media">
    <img src="<?php echo esc_url((string)$module['media']); ?>" alt="<?php echo esc_attr((string)($module['alt'] ?? '')); ?>" loading="lazy" decoding="async">
  </div>
  <?php endif; ?>
</section>
<?php
}

function byl_v3_render_product_module_m02(array $module): void {
    $items = isset($module['items']) && is_array($module['items']) ? array_values(array_filter($module['items'], 'is_array')) : [];
    if (!$items) { return; }
?>
<section class="byl-pdp-module byl-pdp-m02" aria-labelledby="byl-pdp-m02-title">
  <div class="byl-pdp-m02-head">
    <h2 id="byl-pdp-m02-title"><?php echo esc_html((string)($module['title'] ?? 'Highlights')); ?></h2>
    <?php if (!empty($module['intro'])): ?><p><?php echo esc_html((string)$module['intro']); ?></p><?php endif; ?>
  </div>
  <div class="byl-pdp-m02-rail" data-byl-pdp-m02-rail>
    <?php foreach ($items as $item): ?>
    <article class="byl-pdp-m02-card">
      <?php if (!empty($item['media'])): ?><img src="<?php echo esc_url((string)$item['media']); ?>" alt="<?php echo esc_attr((string)($item['alt'] ?? '')); ?>" loading="lazy" decoding="async"><?php endif; ?>
      <div class="byl-pdp-m02-card-copy">
        <?php if (!empty($item['eyebrow'])): ?><div class="byl-pdp-m02-eyebrow"><?php echo esc_html((string)$item['eyebrow']); ?></div><?php endif; ?>
        <h3><?php echo esc_html((string)($item['title'] ?? '')); ?></h3>
        <?php if (!empty($item['copy'])): ?><p><?php echo esc_html((string)$item['copy']); ?></p><?php endif; ?>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
</section>
<?php
}

function byl_v3_render_product_module_m03(array $module): void {
    $video = trim((string)($module['video'] ?? ''));
    if ($video === '') { return; }
?>
<section class="byl-pdp-module byl-pdp-m03" aria-labelledby="byl-pdp-m03-title">
  <div class="byl-pdp-m03-head">
    <div class="byl-pdp-module-kicker">Product Film</div>
    <h2 id="byl-pdp-m03-title"><?php echo esc_html((string)($module['title'] ?? 'See It in Motion.')); ?></h2>
    <?php if (!empty($module['copy'])): ?><p><?php echo esc_html((string)$module['copy']); ?></p><?php endif; ?>
  </div>
  <div class="byl-pdp-m03-stage">
    <video class="byl-pdp-m03-bg" muted loop autoplay playsinline aria-hidden="true" src="<?php echo esc_url($video); ?>"></video>
    <video class="byl-pdp-m03-main" controls muted autoplay playsinline preload="metadata" src="<?php echo esc_url($video); ?>"></video>
  </div>
</section>
<?php
}

function byl_v3_render_product_module_m04(array $module): void {
    $items = isset($module['items']) && is_array($module['items']) ? array_values(array_filter($module['items'], 'is_array')) : [];
    if (count($items) < 3) { return; }
?>
<section class="byl-pdp-module byl-pdp-m04" aria-labelledby="byl-pdp-m04-title">
  <div class="byl-pdp-m04-head"><div class="byl-pdp-module-kicker">Scenario Grid</div><h2 id="byl-pdp-m04-title"><?php echo esc_html((string)($module['title'] ?? 'Made for Every Mood.')); ?></h2></div>
  <div class="byl-pdp-m04-grid">
    <?php foreach (array_slice($items, 0, 3) as $i => $item): ?>
      <?php if ($i === 1): ?><div class="byl-pdp-m04-secondary"><?php endif; ?>
      <article class="byl-pdp-m04-card<?php echo $i === 0 ? ' is-primary' : ''; ?>">
        <?php if (!empty($item['media'])): ?><img src="<?php echo esc_url((string)$item['media']); ?>" alt="<?php echo esc_attr((string)($item['alt'] ?? '')); ?>" loading="lazy" decoding="async"><?php endif; ?>
        <div class="byl-pdp-m04-card-copy"><div class="byl-pdp-m04-eyebrow"><?php echo esc_html((string)($item['eyebrow'] ?? '')); ?></div><h3><?php echo esc_html((string)($item['title'] ?? '')); ?></h3><?php if (!empty($item['copy'])): ?><p><?php echo esc_html((string)$item['copy']); ?></p><?php endif; ?></div>
      </article>
      <?php if ($i === 2): ?></div><?php endif; ?>
    <?php endforeach; ?>
  </div>
</section>
<?php
}

function byl_v3_render_product_module_m05(array $module): void {
    $items = isset($module['items']) && is_array($module['items']) ? array_values(array_filter($module['items'], 'is_array')) : [];
    if (!$items) { return; }
?>
<section class="byl-pdp-module byl-pdp-m05" aria-labelledby="byl-pdp-m05-title">
  <div class="byl-pdp-m05-intro"><div class="byl-pdp-module-kicker"><?php echo esc_html((string)($module['eyebrow'] ?? 'Feature Story')); ?></div><h2 id="byl-pdp-m05-title"><?php echo esc_html((string)($module['title'] ?? '')); ?></h2><?php if (!empty($module['copy'])): ?><p><?php echo esc_html((string)$module['copy']); ?></p><?php endif; ?></div>
  <?php foreach ($items as $item): ?><article class="byl-pdp-m05-story"><div class="byl-pdp-m05-media"><?php if (!empty($item['media'])): ?><img src="<?php echo esc_url((string)$item['media']); ?>" alt="<?php echo esc_attr((string)($item['alt'] ?? '')); ?>" loading="lazy" decoding="async"><?php endif; ?></div><div class="byl-pdp-m05-copy"><div class="byl-pdp-m05-kicker"><?php echo esc_html((string)($item['kicker'] ?? '')); ?></div><h3><?php echo esc_html((string)($item['title'] ?? '')); ?></h3><?php if (!empty($item['copy'])): ?><p><?php echo esc_html((string)$item['copy']); ?></p><?php endif; ?></div></article><?php endforeach; ?>
</section>
<?php
}

function byl_v3_render_product_module_m06(array $module): void {
    $items = isset($module['items']) && is_array($module['items']) ? array_values(array_filter($module['items'], 'is_array')) : [];
    if (!$items) { return; }
?>
<section class="byl-pdp-module byl-pdp-m06" aria-labelledby="byl-pdp-m06-title" data-byl-pdp-m06>
  <div class="byl-pdp-m06-head"><div><div class="byl-pdp-module-kicker">Feature Gallery</div><h2 id="byl-pdp-m06-title"><?php echo esc_html((string)($module['title'] ?? 'Discover More.')); ?></h2></div><?php if (!empty($module['copy'])): ?><p><?php echo esc_html((string)$module['copy']); ?></p><?php endif; ?></div>
  <script type="application/json" class="byl-pdp-m06-data"><?php echo wp_json_encode($items, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
  <div class="byl-pdp-m06-gallery"><article class="byl-pdp-m06-card is-active" data-byl-pdp-m06-active></article><article class="byl-pdp-m06-card is-next" data-byl-pdp-m06-next></article></div>
  <div class="byl-pdp-m06-nav"><button type="button" data-byl-pdp-m06-prev aria-label="Previous feature">←</button><button type="button" data-byl-pdp-m06-next-button aria-label="Next feature">→</button><span data-byl-pdp-m06-count></span></div>
</section>
<?php
}

function byl_v3_render_product_modules(WC_Product $product): void {
    $config = byl_v3_product_module_config($product);
    if (!$config) { return; }
    echo '<div class="byl-pdp-modules" data-byl-pdp-modules>';
    if (byl_v3_product_module_enabled($config, 'm01')) { byl_v3_render_product_module_m01($config['m01']); }
    if (byl_v3_product_module_enabled($config, 'm02')) { byl_v3_render_product_module_m02($config['m02']); }
    if (byl_v3_product_module_enabled($config, 'm03')) { byl_v3_render_product_module_m03($config['m03']); }
    if (byl_v3_product_module_enabled($config, 'm04')) { byl_v3_render_product_module_m04($config['m04']); }
    if (byl_v3_product_module_enabled($config, 'm05')) { byl_v3_render_product_module_m05($config['m05']); }
    if (byl_v3_product_module_enabled($config, 'm06')) { byl_v3_render_product_module_m06($config['m06']); }
    echo '</div>';
}
