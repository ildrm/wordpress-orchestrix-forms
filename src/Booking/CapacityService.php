<?php

declare(strict_types=1);

namespace Orchestrix\Forms\Booking;

final class CapacityService
{
    public function reserve(string $resource, string $slot): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'orchestrix_capacity';
        $updated = $wpdb->query($wpdb->prepare(
            "UPDATE {$table} SET reserved = reserved + 1, updated_at = %s
             WHERE resource_key = %s AND slot_key = %s AND reserved < capacity",
            current_time('mysql', true),
            $resource,
            $slot
        ));
        return $updated === 1;
    }

    public function release(string $resource, string $slot): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'orchestrix_capacity';
        $wpdb->query($wpdb->prepare(
            "UPDATE {$table} SET reserved = GREATEST(0, reserved - 1), updated_at = %s WHERE resource_key = %s AND slot_key = %s",
            current_time('mysql', true),
            $resource,
            $slot
        ));
    }
}
