<?php
/**
 * Plugin Name: BYL Dev G2 Canary Controller
 * Description: Admin-only, hash-pinned Dev MU G2 test patch and rollback. Never deploy to WWW.
 * Version: 0.1.1-dev
 */
defined('ABSPATH') || exit;
function byl_dev_g2ctl_env(): bool {
    return function_exists('wp_get_environment_type')
        && wp_get_environment_type() === 'staging'
        && home_url() === 'https://dev.beyourlover.com'
        && is_ssl();
}
function byl_dev_g2ctl_paths(): array {
    return [
        'target' => WP_CONTENT_DIR . '/mu-plugins/byl-dev-safety.php',
        'payload' => __DIR__ . '/payload/byl-dev-safety.php',
        'backup' => '/home/u247343654/byl-v3-staging/byl-dev-g2-mu-backup-20261103.php',
    ];
}
function byl_dev_g2ctl_hashes(): array {
    return [
        'original' => 'f0979a924a83a320bfa8256ee919d133d44f2f00598757c6720d2489d004ce3b',
        'patched' => '3dd579a0a36a2ae81eae8efb14052f72545bbd78c9ae57d0c3e6798f67297ff4',
    ];
}
function byl_dev_g2ctl_hash(string $path): string {
    return is_file($path) && !is_link($path) && is_readable($path) ? (string) hash_file('sha256', $path) : 'missing';
}
function byl_dev_g2ctl_atomic_write(string $target, string $content): bool {
    if (!is_file($target) || is_link($target) || !is_writable(dirname($target))) { return false; }
    $temp = tempnam(dirname($target), '.byl-g2-');
    if (!$temp) { return false; }
    try {
        if (file_put_contents($temp, $content, LOCK_EX) !== strlen($content)) { return false; }
        chmod($temp, 0644);
        return rename($temp, $target);
    } finally {
        if (is_file($temp)) { unlink($temp); }
    }
}
function byl_dev_g2ctl_apply(bool $rollback): string {
    if (!byl_dev_g2ctl_env() || !current_user_can('manage_options')) { return 'DENIED'; }
    $p = byl_dev_g2ctl_paths(); $h = byl_dev_g2ctl_hashes();
    if (byl_dev_g2ctl_hash($p['payload']) !== $h['patched']) { return 'PAYLOAD_HASH_FAIL'; }
    $current = byl_dev_g2ctl_hash($p['target']);
    $dir = dirname($p['backup']);
    if (!is_dir($dir) || !is_writable($dir) || is_link($p['target'])) { return 'UNSAFE_TARGET_OR_BACKUP_DIR'; }
    $lock = fopen($dir . '/byl-dev-g2-mu-backup.lock', 'c');
    if (!$lock) { return 'LOCK_OPEN_FAILED'; }
    try {
        if (!flock($lock, LOCK_EX)) { return 'LOCK_FAILED'; }
        $current = byl_dev_g2ctl_hash($p['target']);
        if ($rollback) {
            if ($current === $h['original']) { return 'ALREADY_ORIGINAL'; }
            if ($current !== $h['patched'] || byl_dev_g2ctl_hash($p['backup']) !== $h['original']) {
                return 'ROLLBACK_HASH_MISMATCH';
            }
            $original = file_get_contents($p['backup']);
            if (!is_string($original) || !byl_dev_g2ctl_atomic_write($p['target'], $original)) { return 'ROLLBACK_WRITE_FAIL'; }
            return byl_dev_g2ctl_hash($p['target']) === $h['original'] ? 'ROLLED_BACK' : 'ROLLBACK_VERIFY_FAIL';
        }
        if ($current === $h['patched']) { return 'ALREADY_PATCHED'; }
        if ($current !== $h['original']) { return 'CURRENT_HASH_MISMATCH'; }
        if (is_link($p['backup']) || (is_file($p['backup']) && byl_dev_g2ctl_hash($p['backup']) !== $h['original'])) {
            return 'EXISTING_BACKUP_MISMATCH';
        }
        if (!is_file($p['backup'])) {
            $original = file_get_contents($p['target']);
            if (!is_string($original) || file_put_contents($p['backup'], $original, LOCK_EX) !== strlen($original)) {
                return 'BACKUP_WRITE_FAIL';
            }
            chmod($p['backup'], 0600);
            if (byl_dev_g2ctl_hash($p['backup']) !== $h['original']) { return 'BACKUP_VERIFY_FAIL'; }
        }
        $patched = file_get_contents($p['payload']);
        if (!is_string($patched) || !byl_dev_g2ctl_atomic_write($p['target'], $patched)) { return 'PATCH_WRITE_FAIL'; }
        return byl_dev_g2ctl_hash($p['target']) === $h['patched'] ? 'DEV_PATCH_APPLIED' : 'PATCH_VERIFY_FAIL';
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}
add_action('admin_menu', static function (): void {
    if (!byl_dev_g2ctl_env()) { return; }
    add_management_page('BYL Dev G2 Controller', 'BYL Dev G2 Controller', 'manage_options', 'byl-dev-g2-controller', 'byl_dev_g2ctl_page');
});
function byl_dev_g2ctl_page(): void {
    if (!byl_dev_g2ctl_env() || !current_user_can('manage_options')) { wp_die('DENIED'); }
    $p = byl_dev_g2ctl_paths(); $h = byl_dev_g2ctl_hashes();
    echo '<div class="wrap"><h1>BYL Dev G2 Controller</h1><p>Dev only. Exact hash, external backup, atomic rollback. No test permit is created.</p>';
    echo '<p>Current target SHA256: <code>' . esc_html(byl_dev_g2ctl_hash($p['target'])) . '</code></p>';
    echo '<p>Expected original: <code>' . esc_html($h['original']) . '</code></p>';
    echo '<p>Expected Dev Canary: <code>' . esc_html($h['patched']) . '</code></p>';
    echo '<p>Backup: <code>' . esc_html(byl_dev_g2ctl_hash($p['backup'])) . '</code></p>';
    echo '<p>Payload: <code>' . esc_html(byl_dev_g2ctl_hash($p['payload'])) . '</code></p>';
    foreach (['apply' => 'Apply Dev G2 Canary', 'rollback' => 'Rollback to original G2'] as $kind => $label) {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="byl_dev_g2ctl_' . esc_attr($kind) . '">';
        wp_nonce_field('byl_dev_g2ctl_' . $kind);
        echo '<button type="submit" class="button button-secondary">' . esc_html($label) . '</button></form>';
    }
    echo '</div>';
}
foreach (['apply', 'rollback'] as $action) {
    add_action('admin_post_byl_dev_g2ctl_' . $action, static function () use ($action): void {
        if (!byl_dev_g2ctl_env() || !current_user_can('manage_options')) { wp_die('DENIED', '', ['response' => 403]); }
        check_admin_referer('byl_dev_g2ctl_' . $action);
        $result = byl_dev_g2ctl_apply($action === 'rollback');
        if (!in_array($result, ['DEV_PATCH_APPLIED', 'ALREADY_PATCHED', 'ROLLED_BACK', 'ALREADY_ORIGINAL'], true)) {
            wp_die(esc_html($result), '', ['response' => 409]);
        }
        wp_safe_redirect(admin_url('tools.php?page=byl-dev-g2-controller&result=' . rawurlencode($result)));
        exit;
    });
}
