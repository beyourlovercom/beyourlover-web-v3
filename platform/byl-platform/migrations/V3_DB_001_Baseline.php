<?php
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }
return [
    'id' => 'V3_DB_001',
    'description' => 'Register V3 migration baseline; no business data transformation.',
    'up' => static function (): void {},
    'verify' => static function (): bool { return true; },
];
