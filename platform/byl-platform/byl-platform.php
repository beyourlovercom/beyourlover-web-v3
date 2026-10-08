<?php
/**
 * Plugin Name: BYL Platform
 * Description: First-party platform foundation for BYL V3.
 * Version: 0.1.0
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }
define('BYL_PLATFORM_VERSION', '0.1.0');
define('BYL_PLATFORM_DIR', __DIR__);
require_once BYL_PLATFORM_DIR . '/includes/class-byl-migration-runner.php';
require_once BYL_PLATFORM_DIR . '/includes/class-byl-cli.php';
require_once BYL_PLATFORM_DIR . '/includes/class-byl-shipping-promotion-policy.php';
require_once BYL_PLATFORM_DIR . '/includes/class-byl-shipping-promotion-runtime.php';
BYL_Shipping_Promotion_Policy::boot();
BYL_Shipping_Promotion_Runtime::boot();
if (defined('WP_CLI') && WP_CLI) { WP_CLI::add_command('byl migrate', 'BYL_CLI_Migrate_Command'); }
