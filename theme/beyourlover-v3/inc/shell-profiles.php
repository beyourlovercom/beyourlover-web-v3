<?php
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

function byl_v3_shell_profiles(): array {
    return [
        'global' => 'Global',
        'commerce' => 'Commerce',
        'editorial' => 'Editorial',
        'landing' => 'Landing',
        'utility' => 'Utility',
    ];
}

function byl_v3_shell_assignment_defaults(): array {
    return [
        'global' => ['header' => 'global', 'footer' => 'global'],
        'homepage' => ['header' => 'global', 'footer' => 'global'],
        'shop' => ['header' => 'commerce', 'footer' => 'commerce'],
        'product' => ['header' => 'commerce', 'footer' => 'commerce'],
        'blog_archive' => ['header' => 'editorial', 'footer' => 'editorial'],
        'blog_post' => ['header' => 'editorial', 'footer' => 'editorial'],
        'landing' => ['header' => 'landing', 'footer' => 'landing'],
        'support' => ['header' => 'utility', 'footer' => 'utility'],
        'account' => ['header' => 'utility', 'footer' => 'utility'],
    ];
}

function byl_v3_shell_assignments(): array {
    $defaults = byl_v3_shell_assignment_defaults();
    $saved = get_option('byl_v3_shell_assignments', []);
    if (!is_array($saved)) { return $defaults; }
    $profiles = array_keys(byl_v3_shell_profiles());
    foreach ($defaults as $surface => $pair) {
        foreach (['header','footer'] as $part) {
            $candidate = $saved[$surface][$part] ?? null;
            if (is_string($candidate) && in_array($candidate, $profiles, true)) {
                $defaults[$surface][$part] = $candidate;
            }
        }
    }
    return $defaults;
}

function byl_v3_shell_surface(): string {
    if (is_front_page()) { return 'homepage'; }
    if (function_exists('is_checkout') && is_checkout()) { return 'global'; }
    if (function_exists('is_account_page') && is_account_page()) { return 'account'; }
    if (function_exists('is_product') && is_product()) { return 'product'; }
    if (function_exists('is_shop') && (is_shop() || (function_exists('is_product_taxonomy') && is_product_taxonomy()))) { return 'shop'; }
    if (is_home() || is_category() || is_tag() || is_author() || is_date()) { return 'blog_archive'; }
    if (is_singular('post')) { return 'blog_post'; }
    if (is_page()) {
        $id = get_queried_object_id();
        $forced = $id ? (string) get_post_meta($id, '_byl_v3_surface', true) : '';
        if (in_array($forced, ['landing','support'], true)) { return $forced; }
        $template = $id ? (string) get_page_template_slug($id) : '';
        $slug = $id ? (string) get_post_field('post_name', $id) : '';
        if (str_contains($template, 'blog') || $slug === 'blog') { return 'blog_archive'; }
        if (str_contains($template, 'landing')) { return 'landing'; }
        if (in_array($slug, ['support','customer-help','docs','help','faq'], true)) { return 'support'; }
    }
    return 'global';
}

function byl_v3_shell_override(string $part): string {
    if (!in_array($part, ['header','footer'], true) || !is_singular()) { return 'default'; }
    $id = get_queried_object_id();
    if (!$id) { return 'default'; }
    $value = (string) get_post_meta($id, '_byl_v3_'.$part.'_profile', true);
    return in_array($value, array_keys(byl_v3_shell_profiles()), true) ? $value : 'default';
}

function byl_v3_shell_profile(string $part): string {
    $override = byl_v3_shell_override($part);
    if ($override !== 'default') { return $override; }
    $assignments = byl_v3_shell_assignments();
    $surface = byl_v3_shell_surface();
    $fallback = $assignments['global'][$part] ?? 'global';
    $profile = $assignments[$surface][$part] ?? $fallback;
    return array_key_exists($profile, byl_v3_shell_profiles()) ? $profile : 'global';
}

function byl_v3_current_header_profile(): string { return byl_v3_shell_profile('header'); }
function byl_v3_current_footer_profile(): string { return byl_v3_shell_profile('footer'); }

function byl_v3_shell_features(string $profile): array {
    $map = [
        'global' => ['menu'=>true,'search'=>true,'account'=>true,'cart'=>true,'cta'=>false],
        'commerce' => ['menu'=>true,'search'=>true,'account'=>true,'cart'=>true,'cta'=>false],
        'editorial' => ['menu'=>true,'search'=>true,'account'=>false,'cart'=>false,'cta'=>false],
        'landing' => ['menu'=>false,'search'=>false,'account'=>false,'cart'=>false,'cta'=>true],
        'utility' => ['menu'=>true,'search'=>true,'account'=>true,'cart'=>false,'cta'=>false],
    ];
    return $map[$profile] ?? $map['global'];
}

function byl_v3_shell_overlay_enabled(string $profile): bool {
    return ($profile === 'global' && is_front_page()) || $profile === 'landing';
}

function byl_v3_shell_menu_settings(): array {
    $saved = get_option('byl_v3_shell_menus', []);
    return is_array($saved) ? $saved : [];
}

function byl_v3_shell_profile_menu_id(string $profile, bool $mobile = false): int {
    $settings = byl_v3_shell_menu_settings();
    $slot = $mobile ? 'mobile' : 'desktop';
    $configured = isset($settings[$profile][$slot]) ? absint($settings[$profile][$slot]) : 0;
    if ($configured > 0) { return $configured; }
    if (in_array($profile, ['global','commerce'], true)) {
        return byl_v3_menu_id($mobile ? 'mobile' : 'primary');
    }
    return 0;
}

function byl_v3_profile_nav_items(string $profile): array {
    if ($profile === 'editorial') {
        return [
            ['label'=>'Blog','url'=>home_url('/blog/')],
            ['label'=>'Guides','url'=>home_url('/docs/')],
            ['label'=>'Sexual Wellness','url'=>home_url('/category/sexual-wellness/')],
            ['label'=>'Stories','url'=>home_url('/category/stories/')],
        ];
    }
    if ($profile === 'utility') {
        return [
            ['label'=>'Support','url'=>home_url('/customer-help/')],
            ['label'=>'Guides & FAQ','url'=>home_url('/docs/')],
            ['label'=>'Shipping','url'=>home_url('/shipping/')],
            ['label'=>'Returns','url'=>home_url('/returns/')],
        ];
    }
    if ($profile === 'landing') { return []; }
    return byl_v3_fallback_nav_items();
}

function byl_v3_footer_groups(string $profile): array {
    $legal = [
        ['label'=>'Privacy','url'=>home_url('/privacy-policy/')],
        ['label'=>'Terms','url'=>home_url('/terms-and-conditions/')],
        ['label'=>'Accessibility','url'=>home_url('/accessibility/')],
    ];
    if ($profile === 'landing') {
        return [['label'=>'Trust','items'=>array_merge([
            ['label'=>'Shipping','url'=>home_url('/shipping/')],
            ['label'=>'Returns','url'=>home_url('/returns/')],
            ['label'=>'Support','url'=>home_url('/customer-help/')],
        ], $legal)]];
    }
    if ($profile === 'editorial') {
        return [
            ['label'=>'Discover','items'=>[
                ['label'=>'Blog','url'=>home_url('/blog/')],
                ['label'=>'Guides','url'=>home_url('/docs/')],
                ['label'=>'Sexual Wellness','url'=>home_url('/category/sexual-wellness/')],
            ]],
            ['label'=>'Support','items'=>array_merge([['label'=>'Customer Help','url'=>home_url('/customer-help/')]], $legal)],
        ];
    }
    if ($profile === 'commerce') {
        return [
            ['label'=>'Shop','items'=>[
                ['label'=>'All Products','url'=>home_url('/shop/')],
                ['label'=>'New Arrivals','url'=>home_url('/new-arrivals/')],
                ['label'=>'Best Sellers','url'=>home_url('/best-sellers/')],
            ]],
            ['label'=>'Shopping Help','items'=>array_merge([
                ['label'=>'Shipping','url'=>home_url('/shipping/')],
                ['label'=>'Returns','url'=>home_url('/returns/')],
                ['label'=>'Support','url'=>home_url('/customer-help/')],
            ], $legal)],
        ];
    }
    if ($profile === 'utility') {
        return [['label'=>'Help','items'=>array_merge([
            ['label'=>'Customer Help','url'=>home_url('/customer-help/')],
            ['label'=>'Guides & FAQ','url'=>home_url('/docs/')],
            ['label'=>'My Account','url'=>byl_v3_account_url()],
        ], $legal)]];
    }
    return [['label'=>'BeYourLover','items'=>array_merge([
        ['label'=>'Support','url'=>home_url('/customer-help/')],
        ['label'=>'Shipping','url'=>home_url('/shipping/')],
        ['label'=>'Returns','url'=>home_url('/returns/')],
    ], $legal)]];
}

function byl_v3_sanitize_shell_assignments($input): array {
    $defaults = byl_v3_shell_assignment_defaults();
    $profiles = array_keys(byl_v3_shell_profiles());
    if (!is_array($input)) { return $defaults; }
    foreach ($defaults as $surface => $pair) {
        foreach (['header','footer'] as $part) {
            $candidate = isset($input[$surface][$part]) ? sanitize_key((string)$input[$surface][$part]) : '';
            if (in_array($candidate, $profiles, true)) { $defaults[$surface][$part] = $candidate; }
        }
    }
    return $defaults;
}

function byl_v3_sanitize_shell_menus($input): array {
    $clean = [];
    if (!is_array($input)) { return $clean; }
    foreach (array_keys(byl_v3_shell_profiles()) as $profile) {
        $clean[$profile] = [
            'desktop' => absint($input[$profile]['desktop'] ?? 0),
            'mobile' => absint($input[$profile]['mobile'] ?? 0),
        ];
    }
    return $clean;
}

add_action('admin_init', static function (): void {
    register_setting('byl_v3_shell', 'byl_v3_shell_assignments', ['sanitize_callback'=>'byl_v3_sanitize_shell_assignments']);
    register_setting('byl_v3_shell', 'byl_v3_shell_menus', ['sanitize_callback'=>'byl_v3_sanitize_shell_menus']);
});

add_action('admin_menu', static function (): void {
    add_options_page('BYL V3', 'BYL V3', 'manage_options', 'byl-v3-settings', 'byl_v3_render_settings_page');
});

function byl_v3_render_profile_select(string $name, string $selected, bool $allow_default = false): void {
    echo '<select name="'.esc_attr($name).'">';
    if ($allow_default) { echo '<option value="default"'.selected($selected, 'default', false).'>Use Default</option>'; }
    foreach (byl_v3_shell_profiles() as $value => $label) {
        echo '<option value="'.esc_attr($value).'"'.selected($selected, $value, false).'>'.esc_html($label).'</option>';
    }
    echo '</select>';
}

function byl_v3_render_menu_select(string $name, int $selected): void {
    echo '<select name="'.esc_attr($name).'"><option value="0">Profile default</option>';
    foreach (wp_get_nav_menus() as $menu) {
        echo '<option value="'.(int)$menu->term_id.'"'.selected($selected, (int)$menu->term_id, false).'>'.esc_html($menu->name).'</option>';
    }
    echo '</select>';
}

function byl_v3_render_settings_page(): void {
    if (!current_user_can('manage_options')) { return; }
    $assignments = byl_v3_shell_assignments();
    $menus = byl_v3_shell_menu_settings();
    $labels = [
        'global'=>'Global Default','homepage'=>'Homepage','shop'=>'Shop / Category','product'=>'Product',
        'blog_archive'=>'Blog Archive','blog_post'=>'Blog Article','landing'=>'Landing Page',
        'support'=>'Support / Docs','account'=>'Account',
    ];
    ?>
    <div class="wrap">
      <h1>BYL V3</h1>
      <nav class="nav-tab-wrapper"><a class="nav-tab nav-tab-active" href="#">Header &amp; Footer</a></nav>
      <form method="post" action="options.php">
        <?php settings_fields('byl_v3_shell'); ?>
        <h2>Assignment</h2>
        <p>Priority: per-content override &gt; page type assignment &gt; Global Default. Header and Footer resolve independently.</p>
        <table class="widefat striped" style="max-width:900px">
          <thead><tr><th>Surface</th><th>Header</th><th>Footer</th></tr></thead><tbody>
          <?php foreach ($labels as $surface => $label): ?>
            <tr><td><strong><?php echo esc_html($label); ?></strong></td><td>
              <?php byl_v3_render_profile_select('byl_v3_shell_assignments['.$surface.'][header]', $assignments[$surface]['header']); ?>
            </td><td>
              <?php byl_v3_render_profile_select('byl_v3_shell_assignments['.$surface.'][footer]', $assignments[$surface]['footer']); ?>
            </td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <h2 style="margin-top:28px">Menu Sources</h2>
        <p>Presentation remains code-owned. Choose WordPress menu content per Header profile; “Profile default” uses the canonical fallback.</p>
        <table class="widefat striped" style="max-width:900px">
          <thead><tr><th>Profile</th><th>Desktop menu</th><th>Mobile menu</th></tr></thead><tbody>
          <?php foreach (byl_v3_shell_profiles() as $profile => $label): $pair = $menus[$profile] ?? []; ?>
            <tr><td><strong><?php echo esc_html($label); ?></strong></td><td>
              <?php byl_v3_render_menu_select('byl_v3_shell_menus['.$profile.'][desktop]', absint($pair['desktop'] ?? 0)); ?>
            </td><td>
              <?php byl_v3_render_menu_select('byl_v3_shell_menus['.$profile.'][mobile]', absint($pair['mobile'] ?? 0)); ?>
            </td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php submit_button(); ?>
      </form>
    </div>
    <?php
}

add_action('add_meta_boxes', static function (): void {
    foreach (['page','post','product'] as $type) {
        add_meta_box('byl-v3-shell-profile', 'BYL V3 Header & Footer', 'byl_v3_render_shell_meta_box', $type, 'side', 'default');
    }
});

function byl_v3_render_shell_meta_box(WP_Post $post): void {
    wp_nonce_field('byl_v3_shell_meta', 'byl_v3_shell_meta_nonce');
    $header = (string) get_post_meta($post->ID, '_byl_v3_header_profile', true) ?: 'default';
    $footer = (string) get_post_meta($post->ID, '_byl_v3_footer_profile', true) ?: 'default';
    echo '<p><label><strong>Header</strong><br>';
    byl_v3_render_profile_select('byl_v3_header_profile', $header, true);
    echo '</label></p><p><label><strong>Footer</strong><br>';
    byl_v3_render_profile_select('byl_v3_footer_profile', $footer, true);
    echo '</label></p>';
    if ($post->post_type === 'page') {
        $surface = (string) get_post_meta($post->ID, '_byl_v3_surface', true);
        echo '<p><label><strong>Surface type</strong><br><select name="byl_v3_surface">';
        foreach ([''=>'Automatic','landing'=>'Landing Page','support'=>'Support / Docs'] as $value => $label) {
            echo '<option value="'.esc_attr($value).'"'.selected($surface, $value, false).'>'.esc_html($label).'</option>';
        }
        echo '</select></label></p>';
    }
    echo '<p class="description">Use Default follows page type assignment.</p>';
}

add_action('save_post', static function (int $post_id): void {
    if (!isset($_POST['byl_v3_shell_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash((string)$_POST['byl_v3_shell_meta_nonce'])), 'byl_v3_shell_meta')) { return; }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { return; }
    if (!current_user_can('edit_post', $post_id)) { return; }
    $profiles = array_keys(byl_v3_shell_profiles());
    foreach (['header','footer'] as $part) {
        $key = 'byl_v3_'.$part.'_profile';
        $value = isset($_POST[$key]) ? sanitize_key(wp_unslash((string)$_POST[$key])) : 'default';
        if (!in_array($value, $profiles, true)) { $value = 'default'; }
        $value === 'default' ? delete_post_meta($post_id, '_byl_v3_'.$part.'_profile') : update_post_meta($post_id, '_byl_v3_'.$part.'_profile', $value);
    }
    if (get_post_type($post_id) === 'page') {
        $surface = isset($_POST['byl_v3_surface']) ? sanitize_key(wp_unslash((string)$_POST['byl_v3_surface'])) : '';
        if (in_array($surface, ['landing','support'], true)) { update_post_meta($post_id, '_byl_v3_surface', $surface); }
        else { delete_post_meta($post_id, '_byl_v3_surface'); }
    }
});
