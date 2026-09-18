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

function byl_v3_render_product_modules(WC_Product $product): void {
    $config = byl_v3_product_module_config($product);
    if (!$config) { return; }
    echo '<div class="byl-pdp-modules" data-byl-pdp-modules>';
    if (byl_v3_product_module_enabled($config, 'm01')) { byl_v3_render_product_module_m01($config['m01']); }
    if (byl_v3_product_module_enabled($config, 'm02')) { byl_v3_render_product_module_m02($config['m02']); }
    echo '</div>';
}
