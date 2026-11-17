<?php
/**
 * BYL Dev-only A1 HOLD: pause default and WP-CLI Action Scheduler queue runners.
 *
 * Does NOT cancel, delete, complete, or reschedule queued actions. This is
 * a test-site safety gate for real outbound ERP, payments, mail and affiliate
 * integration audits. Admin must approve a separate, controlled unpause
 * once the Dev environment is ready for scheduled side-effects.
 *
 * Scope: only https://dev.beyourlover.com + WP staging environment.
 * Not a guarantee against custom plugin scheduling, external host cron,
 * external ERP pullers, or direct independent API clients.
 */
defined('ABSPATH') || exit;

function byl_a1_dev_scheduler_should_pause(): bool {
    return function_exists('wp_get_environment_type')
        && wp_get_environment_type() === 'staging'
        && function_exists('home_url')
        && untrailingslashit((string) home_url()) === 'https://dev.beyourlover.com';
}

add_filter('action_scheduler_queue_runner_concurrent_batches', static function ($max) {
    return byl_a1_dev_scheduler_should_pause() ? 0 : $max;
}, PHP_INT_MAX, 1);
