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
            'm07' => [
                'title' => 'Small clock. Serious numbers.',
                'copy' => 'The performance story, reduced to the few numbers that matter.',
                'items' => [
                    ['value'=>'6000','unit'=>'RPM ultra-pulse engine','copy'=>'High-speed pulse performance for focused, deeper sensations.'],
                    ['value'=>'185+','unit'=>'vibrations / second','copy'=>'Precision tungsten-steel motor tuned for fast, intense vibration.'],
                    ['value'=>'<55','unit'=>'dB whisper-quiet','copy'=>'Noise-reduction design intended to keep private moments discreet.'],
                    ['value'=>'8+9','unit'=>'suction + vibration modes','copy'=>'Eight suction patterns and nine vibration modes for mix-and-match control.'],
                ],
            ],
            'm08' => [
                'title' => 'Stay close, from anywhere.',
                'copy' => 'Control Naughty Clock Pink in real time through the app — solo or with a partner, even when you’re miles apart.',
                'media' => 'https://cdn.shopify.com/s/files/1/0606/8423/9010/files/naughty_clock_pink_more_features_05.webp?v=1766383071',
                'alt' => 'Naughty Clock Pink app controlled',
                'chips' => ['Real-time control','Solo or partner','Long-distance play'],
            ],
            'm09' => [
                'title' => 'See it in action.',
                'copy' => 'A simple visual guide using the product’s real instructional video.',
                'video' => 'https://cdn.shopify.com/videos/c/o/v/f48b9851eda4412f9f3fb84022f72cb5.mp4',
            ],
            'm10' => [
                'title' => 'Watch It in Real Life.',
                'copy' => 'Real creator videos, one active story at a time.',
                'items' => [
                    ['src'=>'https://img.beyourlover.com/2026/07/14080933/clock-6.mp4','creator'=>'@sandy_blux','url'=>'https://www.instagram.com/p/DatIjJHIgRB/'],
                    ['src'=>'https://img.beyourlover.com/2026/06/12060706/clock-2.mp4','creator'=>'@missfetilicious','url'=>'https://www.instagram.com/missfetilicious_/'],
                    ['src'=>'https://img.beyourlover.com/2026/06/12060759/clock-3.mp4','creator'=>'@byannss_csp','url'=>'https://www.instagram.com/byannss_csp/'],
                    ['src'=>'https://img.beyourlover.com/2026/06/12060816/clock-4.mp4','creator'=>'@heymissbehave','url'=>'https://www.instagram.com/heymissbehave/'],
                ],
            ],
            'm11' => [
                'title' => 'Everything in the box.',
                'items' => [
                    ['name'=>'Packaging Box','media'=>'https://cdn.shopify.com/s/files/1/0606/8423/9010/files/naughty_clock_pink_packing_01.webp?v=1766383070'],
                    ['name'=>'Naughty Clock','media'=>'https://cdn.shopify.com/s/files/1/0606/8423/9010/files/naughty_clock_pink_packing_02.webp?v=1766383070'],
                    ['name'=>'Charging Cable','media'=>'https://cdn.shopify.com/s/files/1/0606/8423/9010/files/naughty_clock_pink_packing_03.webp?v=1766383070'],
                    ['name'=>'User Manual','media'=>'https://cdn.shopify.com/s/files/1/0606/8423/9010/files/naughty_clock_pink_packing_04.webp?v=1766383070'],
                ],
            ],
            'm12' => [
                'title' => 'The details that matter.',
                'media' => 'https://www.beyourlover.com/wp-content/uploads/2026/02/naughty-clock-pink-banner.png',
                'dimensions' => [['Clock','108 × 103 × 59 mm'],['Vibration Wand','104 × 49 × 40 mm'],['Suction Stimulator','48 × 49 × 40 mm']],
                'specs' => [['Material','Silicone + ABS'],['Battery','210 mAh polymer lithium'],['Charging','5V / 200mA · ≤1.5 h'],['Working time','≥40 min'],['Modes','8 suction + 9 vibration'],['Noise','<55 dB'],['Water resistance','IPX6 detachable parts']],
                'note' => 'IPX6 applies to the detachable suction and vibration parts only. The main clock body is not waterproof.',
            ],
            'm13' => [
                'title' => 'Help, when you need it.',
                'faq_url' => 'https://www.beyourlover.com/faqs/',
                'manual_url' => 'https://www.beyourlover.com/wp-content/uploads/2025/03/VA617A-Naughty-Clock-User-Manual.pdf',
                'warranty_url' => 'https://www.beyourlover.com/warranty/',
                'guarantee' => '10 yr',
                'replacement' => '1 yr',
            ],
            'm14' => [
                'title' => 'Find Your Perfect Match',
                'products' => ['naughty-clock-pink','fatima-app'],
                'rows' => [
                    ['Best for','Daily fun & discreet bedside play','Deeper, multi-sensation play'],
                    ['Control','App control','App + remote'],
                    ['Stimulation','Vibration + suction','Suction + thrusting + vibration + heating'],
                    ['Modes','9 vibration + 8 suction','10 vibration + 7 thrusting + suction patterns'],
                    ['Water resistance','IPX6 detachable parts','IPX7 full waterproof'],
                ],
            ],
            'm15' => [
                'title' => 'Your Pleasure. Our Promise.',
                'copy' => 'Discreet shipping, secure checkout, and real support — so you can shop with total confidence.',
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

function byl_v3_render_product_module_m07(array $module): void {
    $items = isset($module['items']) && is_array($module['items']) ? array_values(array_filter($module['items'], 'is_array')) : [];
    if (!$items) { return; }
?>
<section class="byl-pdp-module byl-pdp-m07" aria-labelledby="byl-pdp-m07-title">
  <div class="byl-pdp-m07-copy"><div class="byl-pdp-module-kicker">Naughty Clock · Performance</div><h2 id="byl-pdp-m07-title"><?php echo esc_html((string)($module['title'] ?? '')); ?></h2><?php if (!empty($module['copy'])): ?><p><?php echo esc_html((string)$module['copy']); ?></p><?php endif; ?></div>
  <div class="byl-pdp-m07-metrics"><?php foreach ($items as $item): ?><article class="byl-pdp-m07-metric"><div class="byl-pdp-m07-value"><?php echo esc_html((string)($item['value'] ?? '')); ?></div><div class="byl-pdp-m07-unit"><?php echo esc_html((string)($item['unit'] ?? '')); ?></div><?php if (!empty($item['copy'])): ?><p><?php echo esc_html((string)$item['copy']); ?></p><?php endif; ?></article><?php endforeach; ?></div>
</section>
<?php
}

function byl_v3_render_product_module_m08(array $module): void {
    if (empty($module['media'])) { return; }
    $chips = isset($module['chips']) && is_array($module['chips']) ? $module['chips'] : [];
?>
<section class="byl-pdp-module byl-pdp-m08" aria-labelledby="byl-pdp-m08-title">
  <div class="byl-pdp-m08-wrap">
    <div class="byl-pdp-m08-copy"><div class="byl-pdp-module-kicker">App Ecosystem</div><h2 id="byl-pdp-m08-title"><?php echo esc_html((string)($module['title'] ?? '')); ?></h2><?php if (!empty($module['copy'])): ?><p><?php echo esc_html((string)$module['copy']); ?></p><?php endif; ?><?php if ($chips): ?><div class="byl-pdp-m08-chips"><?php foreach ($chips as $chip): ?><span><?php echo esc_html((string)$chip); ?></span><?php endforeach; ?></div><?php endif; ?></div>
    <div class="byl-pdp-m08-media"><img src="<?php echo esc_url((string)$module['media']); ?>" alt="<?php echo esc_attr((string)($module['alt'] ?? '')); ?>" loading="lazy" decoding="async"></div>
  </div>
</section>
<?php
}

function byl_v3_render_product_module_m09(array $module): void {
    $video = trim((string)($module['video'] ?? ''));
    if ($video === '') { return; }
?>
<section class="byl-pdp-module byl-pdp-m09" aria-labelledby="byl-pdp-m09-title">
  <div class="byl-pdp-m09-wrap"><div class="byl-pdp-module-kicker">How to Use</div><h2 id="byl-pdp-m09-title"><?php echo esc_html((string)($module['title'] ?? '')); ?></h2><?php if (!empty($module['copy'])): ?><p><?php echo esc_html((string)$module['copy']); ?></p><?php endif; ?><div class="byl-pdp-m09-stage"><video controls muted playsinline preload="metadata" src="<?php echo esc_url($video); ?>"></video></div></div>
</section>
<?php
}

function byl_v3_render_product_module_m10(array $module): void {
    $items = isset($module['items']) && is_array($module['items']) ? array_values(array_filter($module['items'], 'is_array')) : [];
    if (!$items) { return; }
?>
<section class="byl-pdp-module byl-pdp-m10" aria-labelledby="byl-pdp-m10-title" data-byl-pdp-m10>
  <div class="byl-pdp-m10-head"><div class="byl-pdp-module-kicker">Use Video Gallery</div><h2 id="byl-pdp-m10-title"><?php echo esc_html((string)($module['title'] ?? '')); ?></h2><?php if (!empty($module['copy'])): ?><p><?php echo esc_html((string)$module['copy']); ?></p><?php endif; ?></div>
  <script type="application/json" class="byl-pdp-m10-data"><?php echo wp_json_encode($items, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
  <div class="byl-pdp-m10-stage" data-byl-pdp-m10-stage></div>
  <div class="byl-pdp-m10-mobile-rail" data-byl-pdp-m10-mobile-rail><article class="byl-pdp-m10-mobile-card is-active" data-byl-pdp-m10-mobile-active></article><article class="byl-pdp-m10-mobile-card is-next" data-byl-pdp-m10-mobile-next></article></div>
  <div class="byl-pdp-m10-nav"><button type="button" data-byl-pdp-m10-prev aria-label="Previous video">←</button><span data-byl-pdp-m10-count></span><button type="button" data-byl-pdp-m10-next aria-label="Next video">→</button></div>
  <div class="byl-pdp-m10-swipe-hint">SWIPE TO EXPLORE</div>
</section>
<?php
}

function byl_v3_render_product_module_m11(array $m): void {
 $items=is_array($m['items']??null)?$m['items']:[]; if(!$items)return; ?>
<section class="byl-pdp-module byl-pdp-m11"><div class="byl-pdp-section-head"><div class="byl-pdp-module-kicker">What's Included</div><h2><?php echo esc_html((string)($m['title']??'')); ?></h2></div><div class="byl-pdp-m11-grid"><?php foreach($items as $i=>$it): ?><article><img src="<?php echo esc_url((string)$it['media']); ?>" alt="<?php echo esc_attr((string)$it['name']); ?>" loading="lazy"><span><?php echo esc_html(str_pad((string)($i+1),2,'0',STR_PAD_LEFT)); ?></span><h3><?php echo esc_html((string)$it['name']); ?></h3></article><?php endforeach; ?></div></section><?php
}
function byl_v3_render_product_module_m12(array $m): void {
 $dims=is_array($m['dimensions']??null)?$m['dimensions']:[];$specs=is_array($m['specs']??null)?$m['specs']:[]; ?>
<section class="byl-pdp-module byl-pdp-m12"><div class="byl-pdp-section-head"><div class="byl-pdp-module-kicker">Specs & Dimensions</div><h2><?php echo esc_html((string)($m['title']??'')); ?></h2></div><div class="byl-pdp-m12-stage"><div class="byl-pdp-m12-visual"><?php if(!empty($m['media'])): ?><img src="<?php echo esc_url((string)$m['media']); ?>" alt="Naughty Clock Pink" loading="lazy"><?php endif; ?><div class="byl-pdp-m12-dims"><?php foreach($dims as $d): ?><div><b><?php echo esc_html((string)$d[1]); ?></b><span><?php echo esc_html((string)$d[0]); ?></span></div><?php endforeach; ?></div></div><div class="byl-pdp-m12-specs"><?php foreach($specs as $s): ?><div><span><?php echo esc_html((string)$s[0]); ?></span><b><?php echo esc_html((string)$s[1]); ?></b></div><?php endforeach; ?><?php if(!empty($m['note'])): ?><p><?php echo esc_html((string)$m['note']); ?></p><?php endif; ?></div></div></section><?php
}
function byl_v3_render_product_module_m13(array $m): void { ?>
<section class="byl-pdp-module byl-pdp-m13"><div class="byl-pdp-section-head"><div class="byl-pdp-module-kicker">Support & Documents</div><h2><?php echo esc_html((string)($m['title']??'')); ?></h2></div><div class="byl-pdp-m13-grid"><a href="<?php echo esc_url((string)$m['faq_url']); ?>"><small>POPULAR FAQS</small><h3>Quick answers.</h3><span>View FAQs →</span></a><a class="is-dark" href="<?php echo esc_url((string)$m['manual_url']); ?>" target="_blank" rel="nofollow noopener"><small>USER MANUAL</small><h3>Read the guide.</h3><span>Download manual ↓</span></a><a href="<?php echo esc_url((string)$m['warranty_url']); ?>"><small>WARRANTY</small><div class="byl-pdp-m13-warranty"><div><b><?php echo esc_html((string)$m['guarantee']); ?></b><span>Quality Guarantee</span></div><div><b><?php echo esc_html((string)$m['replacement']); ?></b><span>Free Replacement</span></div></div><span>Warranty details →</span></a></div></section><?php
}
function byl_v3_render_product_module_m14(array $m): void {
 $slugs=is_array($m['products']??null)?$m['products']:[];$ps=[];foreach($slugs as $slug){$post=get_page_by_path((string)$slug,OBJECT,'product');if($post instanceof WP_Post){$wp=wc_get_product($post->ID);if($wp instanceof WC_Product)$ps[]=$wp;}} if(count($ps)<2)return; ?>
<section class="byl-pdp-module byl-pdp-m14"><div class="byl-pdp-section-head"><div class="byl-pdp-module-kicker">Compare Models</div><h2><?php echo esc_html((string)($m['title']??'')); ?></h2></div><div class="byl-pdp-m14-products"><?php foreach($ps as $wp): ?><article><?php echo $wp->get_image('woocommerce_single'); ?><h3><?php echo esc_html($wp->get_name()); ?></h3><div><?php echo wp_kses_post($wp->get_price_html()); ?></div><a href="<?php echo esc_url(get_permalink($wp->get_id())); ?>">View Product</a></article><?php endforeach; ?></div><div class="byl-pdp-m14-table"><?php foreach((array)($m['rows']??[]) as $r): ?><div><b><?php echo esc_html((string)$r[0]); ?></b><span><?php echo esc_html((string)$r[1]); ?></span><span><?php echo esc_html((string)$r[2]); ?></span></div><?php endforeach; ?></div></section><?php
}
function byl_v3_render_product_module_m15(array $m): void { ?>
<section class="byl-pdp-module byl-pdp-m15"><div class="byl-pdp-section-head"><div class="byl-pdp-module-kicker">Trust & Service</div><h2><?php echo esc_html((string)($m['title']??'')); ?></h2><p><?php echo esc_html((string)($m['copy']??'')); ?></p></div><div class="byl-pdp-m15-services"><article><h3>Discreet Shipping</h3><p>Plain packaging with no BeYourLover or adult company name visible.</p></article><article><h3>Secure Checkout</h3><p>Secure purchase flow on BeYourLover.</p></article><article><h3>Returns Support</h3><p>30-day return policy for eligible unused products in original packaging.</p></article><article><h3>Real Support</h3><p>Email, live chat and support resources are available.</p></article></div><div class="byl-pdp-m15-links"><a href="https://www.beyourlover.com/shipping-policies/">Shipping & Tracking</a><a href="https://www.beyourlover.com/return-policies/">Returns Policy</a><a href="https://www.beyourlover.com/customer-help/">Customer Help</a></div></section><?php
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
    if (byl_v3_product_module_enabled($config, 'm07')) { byl_v3_render_product_module_m07($config['m07']); }
    if (byl_v3_product_module_enabled($config, 'm08')) { byl_v3_render_product_module_m08($config['m08']); }
    if (byl_v3_product_module_enabled($config, 'm09')) { byl_v3_render_product_module_m09($config['m09']); }
    if (byl_v3_product_module_enabled($config, 'm10')) { byl_v3_render_product_module_m10($config['m10']); }
    if (byl_v3_product_module_enabled($config, 'm11')) { byl_v3_render_product_module_m11($config['m11']); }
    if (byl_v3_product_module_enabled($config, 'm12')) { byl_v3_render_product_module_m12($config['m12']); }
    if (byl_v3_product_module_enabled($config, 'm13')) { byl_v3_render_product_module_m13($config['m13']); }
    if (byl_v3_product_module_enabled($config, 'm14')) { byl_v3_render_product_module_m14($config['m14']); }
    if (byl_v3_product_module_enabled($config, 'm15')) { byl_v3_render_product_module_m15($config['m15']); }
    echo '</div>';
}
