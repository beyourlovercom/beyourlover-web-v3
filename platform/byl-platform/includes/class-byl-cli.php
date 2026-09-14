<?php
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }
final class BYL_CLI_Migrate_Command {
    public function __invoke(array $args, array $assoc_args): void {
        $pending = BYL_Migration_Runner::pending();
        if ($pending === []) { WP_CLI::success('No pending BYL V3 migrations.'); return; }
        foreach ($pending as $id => $m) { WP_CLI::log($id.' — '.($m['description'] ?? 'no description')); }
        if (!isset($assoc_args['apply'])) { WP_CLI::warning('Dry-run only. Re-run with --apply.'); return; }
        foreach (array_keys($pending) as $id) { WP_CLI::log("Applying {$id}..."); BYL_Migration_Runner::apply($id); }
        WP_CLI::success('Pending BYL V3 migrations applied and verified.');
    }
}
