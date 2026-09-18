<?php

declare(strict_types=1);

if (! defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}
if (! defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}
if (! defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 3600);
}
if (! defined('ORCHESTRIX_FORMS_FILE')) {
    define('ORCHESTRIX_FORMS_FILE', __FILE__);
}
if (! defined('ORCHESTRIX_FORMS_DIR')) {
    define('ORCHESTRIX_FORMS_DIR', dirname(__DIR__) . '/');
}
if (! defined('ORCHESTRIX_FORMS_URL')) {
    define('ORCHESTRIX_FORMS_URL', 'https://example.test/wp-content/plugins/orchestrix-forms/');
}
if (! defined('ORCHESTRIX_FORMS_VERSION')) {
    define('ORCHESTRIX_FORMS_VERSION', '1.0.0');
}
if (! class_exists('WP_CLI')) {
    class WP_CLI
    {
        public static function add_command(string $name, object $command): void
        {
        }
    }
}
