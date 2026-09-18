<?php

declare(strict_types=1);

namespace Orchestrix\Forms\Submissions;

use Orchestrix\Forms\Core\DomainException;
use Orchestrix\Forms\Security\SecretVault;
use RuntimeException;

final class SubmissionRepository
{
    public function __construct(private readonly SecretVault $secrets = new SecretVault())
    {
    }

    /**
     * @param array<string,mixed> $values
     * @param array<string,array<string,mixed>> $fields
     */
    public function create(int $formId, int $versionId, array $values, array $fields, int $spamScore, bool $test, string $sourceUrl, string $identity): int
    {
        global $wpdb;
        $now = current_time('mysql', true);
        $wpdb->query('START TRANSACTION');
        try {
            $inserted = $wpdb->insert($wpdb->prefix . 'orchestrix_submissions', [
                'uuid' => wp_generate_uuid4(), 'form_id' => $formId, 'form_version_id' => $versionId,
                'user_id' => get_current_user_id() ?: null, 'status' => $spamScore >= 70 ? 'spam' : 'completed',
                'is_test' => $test ? 1 : 0, 'spam_score' => $spamScore, 'source_url' => esc_url_raw($sourceUrl),
                'ip_hash' => hash_hmac('sha256', $identity, wp_salt('auth')),
                'user_agent_hash' => hash_hmac('sha256', substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500), wp_salt('auth')),
                'created_at' => $now, 'updated_at' => $now,
            ]);
            if ($inserted !== 1) {
                throw new \RuntimeException($wpdb->last_error);
            }
            $submissionId = (int) $wpdb->insert_id;
            foreach ($values as $key => $value) {
                $field = $fields[$key] ?? [];
                $serialized = is_scalar($value) || $value === null ? (string) ($value ?? '') : (wp_json_encode($value) ?: '');
                $privacy = in_array(($field['privacy'] ?? ''), ['public', 'personal', 'sensitive', 'secret'], true)
                    ? (string) $field['privacy']
                    : 'personal';
                $scope = $this->uniqueScope((string) ($field['unique'] ?? ''), get_current_user_id());
                $valueHash = $serialized === '' ? null : hash_hmac('sha256', $serialized, wp_salt('secure_auth'));
                $stored = $privacy === 'secret' && $serialized !== ''
                    ? 'encrypted:v1:' . $this->secrets->encrypt($serialized)
                    : $serialized;
                $result = $wpdb->insert($wpdb->prefix . 'orchestrix_submission_values', [
                    'submission_id' => $submissionId, 'form_id' => $formId, 'field_key' => $key,
                    'value_text' => $stored,
                    'value_number' => $privacy !== 'secret' && is_numeric($value) ? (float) $value : null,
                    'value_date' => $privacy !== 'secret' ? $this->dateValue($value, (string) ($field['type'] ?? '')) : null,
                    'privacy' => $privacy,
                    'value_hash' => $valueHash,
                    'unique_scope' => $scope,
                ]);
                if ($result !== 1) {
                    if ($scope !== null && str_contains(strtolower($wpdb->last_error), 'duplicate')) {
                        throw new DomainException(__('A value that must be unique has already been submitted.', 'orchestrix-forms'), 'duplicate_value', 409);
                    }
                    throw new \RuntimeException($wpdb->last_error);
                }
            }
            $wpdb->query('COMMIT');
            return $submissionId;
        } catch (DomainException $error) {
            $wpdb->query('ROLLBACK');
            throw $error;
        } catch (\Throwable) {
            $wpdb->query('ROLLBACK');
            throw new DomainException(__('Your submission could not be saved. Please try again.', 'orchestrix-forms'), 'submission_failed', 500);
        }
    }

    /** @return array<string,mixed>|null */
    public function find(int $id, bool $includeSecrets = false): ?array
    {
        global $wpdb;
        $submission = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}orchestrix_submissions WHERE id = %d", $id), ARRAY_A);
        if (! is_array($submission)) {
            return null;
        }
        $rows = $wpdb->get_results($wpdb->prepare("SELECT field_key,value_text,privacy FROM {$wpdb->prefix}orchestrix_submission_values WHERE submission_id = %d ORDER BY id", $id), ARRAY_A) ?: [];
        $submission['values'] = [];
        foreach ($rows as $row) {
            $stored = (string) $row['value_text'];
            if ($row['privacy'] === 'secret') {
                if (! $includeSecrets) {
                    $submission['values'][(string) $row['field_key']] = null;
                    continue;
                }
                if (! str_starts_with($stored, 'encrypted:v1:')) {
                    $submission['values'][(string) $row['field_key']] = null;
                    continue;
                }
                try {
                    $stored = $this->secrets->decrypt(substr($stored, 13));
                } catch (RuntimeException) {
                    $submission['values'][(string) $row['field_key']] = null;
                    continue;
                }
            }
            $decoded = json_decode($stored, true);
            $submission['values'][(string) $row['field_key']] = json_last_error() === JSON_ERROR_NONE && is_array($decoded)
                ? $decoded
                : $stored;
        }
        return $submission;
    }

    /** @return list<array<string,mixed>> */
    public function list(int $page, int $perPage, ?int $formId = null): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'orchestrix_submissions';
        $limit = min(100, max(1, $perPage));
        $offset = max(0, ($page - 1) * $limit);
        if ($formId !== null) {
            return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE form_id = %d ORDER BY id DESC LIMIT %d OFFSET %d", $formId, $limit, $offset), ARRAY_A) ?: [];
        }
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} ORDER BY id DESC LIMIT %d OFFSET %d", $limit, $offset), ARRAY_A) ?: [];
    }

    private function uniqueScope(string $mode, int $userId): ?string
    {
        return match (true) {
            $mode === 'global' => hash('sha256', 'global'),
            $mode === 'user' && $userId > 0 => hash('sha256', 'user:' . $userId),
            default => null,
        };
    }

    private function dateValue(mixed $value, string $type): ?string
    {
        if (! in_array($type, ['date', 'datetime', 'timezone_datetime'], true) || ! is_string($value)) {
            return null;
        }
        $timestamp = strtotime($value);
        return $timestamp === false ? null : gmdate('Y-m-d H:i:s', $timestamp);
    }
}
