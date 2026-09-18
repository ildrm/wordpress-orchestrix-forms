<?php

declare(strict_types=1);

namespace Orchestrix\Forms\Core;

use Orchestrix\Forms\Migrations\Schema;

final class Activation
{
    public static function activate(bool $networkWide = false): void
    {
        if (is_multisite() && $networkWide) {
            $siteIds = get_sites(['fields' => 'ids', 'number' => 0]);
            foreach ($siteIds as $siteId) {
                switch_to_blog((int) $siteId);
                self::activateSite();
                restore_current_blog();
            }
        } else {
            self::activateSite();
        }
    }

    public static function deactivate(): void
    {
        wp_clear_scheduled_hook('orchestrix_forms_process_jobs');
        wp_clear_scheduled_hook('orchestrix_forms_cleanup');
    }

    private static function activateSite(): void
    {
        (new Schema())->migrate();
        Capabilities::install();
        if (! wp_next_scheduled('orchestrix_forms_process_jobs')) {
            wp_schedule_event(time() + 60, 'minute', 'orchestrix_forms_process_jobs');
        }
        if (! wp_next_scheduled('orchestrix_forms_cleanup')) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'orchestrix_forms_cleanup');
        }
    }
}
