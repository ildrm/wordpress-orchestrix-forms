<?php

/**
 * Plugin Name: Orchestrix Forms
 * Plugin URI: https://github.com/ildrm/wordpress-orchestrix-forms
 * Description: A high-performance, secure, extensible WordPress form, workflow, automation, data and low-code application platform.
 * Version: 1.0.0
 * Author: Shahin Ilderemi
 * Author URI: https://ildrm.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: orchestrix-forms
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.1
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

define('ORCHESTRIX_FORMS_VERSION', '1.0.0');
define('ORCHESTRIX_FORMS_FILE', __FILE__);
define('ORCHESTRIX_FORMS_DIR', plugin_dir_path(__FILE__));
define('ORCHESTRIX_FORMS_URL', plugin_dir_url(__FILE__));

$composer = ORCHESTRIX_FORMS_DIR . 'vendor/autoload.php';
if (is_readable($composer)) {
    require $composer;
} else {
    require ORCHESTRIX_FORMS_DIR . 'src/Core/Autoloader.php';
    \Orchestrix\Forms\Core\Autoloader::register(ORCHESTRIX_FORMS_DIR . 'src');
}

register_activation_hook(__FILE__, [\Orchestrix\Forms\Core\Activation::class, 'activate']);
register_deactivation_hook(__FILE__, [\Orchestrix\Forms\Core\Activation::class, 'deactivate']);

add_filter('cron_schedules', static function (array $schedules): array {
    $schedules['minute'] = ['interval' => 60, 'display' => __('Every minute', 'orchestrix-forms')];
    return $schedules;
});

add_action('plugins_loaded', static function (): void {
    \Orchestrix\Forms\Plugin::instance()->boot();
});
