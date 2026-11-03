<?php
/**
 * Plugin Name: BYL Dev G2 MU Source Readback
 * Description: Dev-only admin-scoped read of the MU safety code for accurate source/runtime reconciliation. No writes.
 * Version: 0.1.0-dev
 */
defined('ABSPATH') || exit;
if (wp_get_environment_type() !== 'staging' || home_url() !== 'https://dev.beyourlover.com') { return; }
add_action('admin_menu', static function (): void {
    add_management_page('BYL Dev G2 Readback', 'BYL Dev G2 Readback', 'manage_options', 'byl-dev-g2-readback', static function (): void {
        if (!current_user_can('manage_options') || home_url() !== 'https://dev.beyourlover.com') {
            wp_die('DENIED', '', ['response' => 403]);
        }
        $p = WP_CONTENT_DIR . '/mu-plugins/byl-dev-safety.php';
        if (!is_file($p) || is_link($p) || !is_readable($p)) { wp_die('MU file unreadable'); }
        $s = file_get_contents($p);
        if (!is_string($s) || strlen($s) > 100000) { wp_die('MU source size unsupported'); }
        echo '<div class="wrap"><h1>BYL Dev MU Readback</h1><p>Admin only; no updates or public endpoints.</p>';
        echo '<p>SHA256 <code>' . esc_html(hash('sha256', $s)) . '</code></p>';
        echo '<textarea id="byl-dev-g2-source" style="display:none">' . esc_textarea(base64_encode($s)) . '</textarea></div>';
    });
});
