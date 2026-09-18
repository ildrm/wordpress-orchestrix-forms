<?php

declare(strict_types=1);

namespace Orchestrix\Forms\Core;

final class Capabilities
{
    /** @return list<string> */
    public static function all(): array
    {
        return [
            'orchestrix_manage_forms', 'orchestrix_edit_forms', 'orchestrix_publish_forms',
            'orchestrix_view_entries', 'orchestrix_edit_entries', 'orchestrix_delete_entries',
            'orchestrix_export_entries', 'orchestrix_manage_integrations',
            'orchestrix_manage_payments', 'orchestrix_manage_workflows',
            'orchestrix_manage_settings', 'orchestrix_view_sensitive_fields',
        ];
    }

    public static function install(): void
    {
        $role = get_role('administrator');
        if ($role === null) {
            return;
        }
        foreach (self::all() as $capability) {
            $role->add_cap($capability);
        }
    }
}
