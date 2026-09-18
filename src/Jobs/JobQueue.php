<?php

declare(strict_types=1);

namespace Orchestrix\Forms\Jobs;

use Orchestrix\Forms\Core\DomainException;

final class JobQueue
{
    /** @param array<string,mixed> $payload */
    public function dispatch(string $type, array $payload, ?string $idempotencyKey = null, int $delay = 0, int $maxAttempts = 5): string
    {
        global $wpdb;
        $uuid = wp_generate_uuid4();
        $now = current_time('mysql', true);
        $inserted = $wpdb->insert($wpdb->prefix . 'orchestrix_jobs', [
            'uuid' => $uuid, 'type' => sanitize_key($type), 'payload_json' => wp_json_encode($payload),
            'status' => 'pending', 'attempts' => 0, 'max_attempts' => min(20, max(1, $maxAttempts)),
            'available_at' => gmdate('Y-m-d H:i:s', time() + max(0, $delay)),
            'idempotency_key' => $idempotencyKey, 'created_at' => $now, 'updated_at' => $now,
        ], ['%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s']);
        if ($inserted !== 1 && $idempotencyKey === null) {
            throw new DomainException(__('The background task could not be queued.', 'orchestrix-forms'), 'queue_failed', 500);
        }
        return $uuid;
    }

    public function process(int $limit = 10): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'orchestrix_jobs';
        $now = current_time('mysql', true);
        $leaseExpired = gmdate('Y-m-d H:i:s', time() - 15 * 60);
        $wpdb->query($wpdb->prepare(
            "UPDATE {$table}
             SET status = IF(attempts + 1 >= max_attempts, 'failed', 'retrying'),
                 attempts = attempts + 1, available_at = %s, locked_at = NULL,
                 last_error = 'Worker lease expired.', updated_at = %s
             WHERE status = 'running' AND locked_at < %s",
            $now,
            $now,
            $leaseExpired
        ));
        $processed = 0;
        for ($index = 0; $index < min(100, max(1, $limit)); $index++) {
            $wpdb->query('START TRANSACTION');
            $job = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table} WHERE status IN ('pending','retrying') AND available_at <= %s
                 ORDER BY available_at,id LIMIT 1 FOR UPDATE",
                current_time('mysql', true)
            ), ARRAY_A);
            if (! is_array($job)) {
                $wpdb->query('COMMIT');
                break;
            }
            $wpdb->update($table, ['status' => 'running', 'locked_at' => current_time('mysql', true), 'updated_at' => current_time('mysql', true)], ['id' => (int) $job['id']], ['%s', '%s', '%s'], ['%d']);
            $wpdb->query('COMMIT');
            try {
                $payload = json_decode((string) $job['payload_json'], true, 32, JSON_THROW_ON_ERROR);
                do_action('orchestrix_forms_job_' . sanitize_key((string) $job['type']), $payload, $job);
                $wpdb->update($table, [
                    'status' => 'succeeded', 'updated_at' => current_time('mysql', true),
                    'last_error' => null, 'locked_at' => null,
                ], ['id' => (int) $job['id']], ['%s', '%s', '%s', '%s'], ['%d']);
            } catch (\Throwable $error) {
                $attempts = ((int) $job['attempts']) + 1;
                $terminal = $attempts >= (int) $job['max_attempts'];
                $wpdb->update($table, [
                    'status' => $terminal ? 'failed' : 'retrying', 'attempts' => $attempts,
                    'available_at' => gmdate('Y-m-d H:i:s', time() + min(3600, 30 * (2 ** min(7, $attempts)))),
                    'last_error' => substr($error->getMessage(), 0, 2000),
                    'updated_at' => current_time('mysql', true), 'locked_at' => null,
                ], ['id' => (int) $job['id']], ['%s', '%d', '%s', '%s', '%s', '%s'], ['%d']);
            }
            $processed++;
        }
        return $processed;
    }
}
