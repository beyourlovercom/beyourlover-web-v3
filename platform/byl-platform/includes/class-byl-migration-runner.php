<?php
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }
final class BYL_Migration_Runner {
    private const APPLIED_OPTION = 'byl_v3_applied_migrations';
    public static function discover(): array {
        $files = glob(BYL_PLATFORM_DIR . '/migrations/V3_DB_*.php') ?: []; sort($files, SORT_STRING); $out = [];
        foreach ($files as $file) { $m = require $file; if (!is_array($m) || empty($m['id'])) { throw new RuntimeException('Invalid migration: '.basename($file)); } $out[(string)$m['id']] = $m; }
        return $out;
    }
    public static function applied(): array { $v = get_option(self::APPLIED_OPTION, []); return is_array($v) ? $v : []; }
    public static function pending(): array { return array_diff_key(self::discover(), self::applied()); }
    public static function apply(string $id): void {
        $all = self::discover(); if (!isset($all[$id])) { throw new RuntimeException("Unknown migration: {$id}"); }
        $applied = self::applied(); if (isset($applied[$id])) { return; } $m = $all[$id]; ($m['up'])();
        if (isset($m['verify']) && !(bool)($m['verify'])()) { throw new RuntimeException("Verification failed: {$id}"); }
        $applied[$id] = ['applied_at'=>gmdate('c'),'release_id'=>defined('BYL_RELEASE_ID')?BYL_RELEASE_ID:null];
        update_option(self::APPLIED_OPTION, $applied, false);
    }
}
