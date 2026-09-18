<?php

declare(strict_types=1);

namespace Orchestrix\Forms\Forms;

use Orchestrix\Forms\Core\DomainException;

final class FormRepository
{
    /**
     * @param array<string,mixed> $schema
     * @param array<string,mixed> $settings
     */
    public function create(string $name, array $schema, array $settings, int $actorId): int
    {
        global $wpdb;
        $now = current_time('mysql', true);
        $slugBase = sanitize_title($name) ?: 'form';
        $slug = wp_unique_id($slugBase . '-');
        $wpdb->query('START TRANSACTION');
        try {
            $inserted = $wpdb->insert($wpdb->prefix . 'orchestrix_forms', [
                'uuid' => wp_generate_uuid4(), 'name' => sanitize_text_field($name), 'slug' => $slug,
                'status' => 'draft', 'settings' => wp_json_encode($settings), 'created_by' => $actorId,
                'created_at' => $now, 'updated_at' => $now,
            ], ['%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s']);
            if ($inserted !== 1) {
                throw new DomainException(
                    __('The form could not be created.', 'orchestrix-forms'),
                    'form_create_failed',
                    500
                );
            }
            $formId = (int) $wpdb->insert_id;
            $this->insertVersion($formId, 1, 'draft', $schema, $actorId);
            $wpdb->query('COMMIT');
            return $formId;
        } catch (DomainException $error) {
            $wpdb->query('ROLLBACK');
            throw $error;
        } catch (\Throwable) {
            $wpdb->query('ROLLBACK');
            throw new DomainException(
                __('The form could not be created.', 'orchestrix-forms'),
                'form_create_failed',
                500
            );
        }
    }

    /** @param array<string,mixed> $schema */
    public function saveDraft(int $formId, array $schema, int $actorId): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'orchestrix_form_versions';
        $latest = $wpdb->get_row($wpdb->prepare("SELECT id, version, state FROM {$table} WHERE form_id = %d ORDER BY version DESC LIMIT 1", $formId), ARRAY_A);
        if (! is_array($latest)) {
            throw new DomainException(__('Form not found.', 'orchestrix-forms'), 'form_not_found', 404);
        }
        if ($latest['state'] === 'draft') {
            $json = $this->encodeSchema($schema);
            $wpdb->update($table, [
                'schema_json' => $json, 'schema_hash' => hash('sha256', $json),
                'created_by' => $actorId, 'created_at' => current_time('mysql', true),
            ], ['id' => (int) $latest['id']], ['%s', '%s', '%d', '%s'], ['%d']);
            $versionId = (int) $latest['id'];
        } else {
            $versionId = $this->insertVersion($formId, ((int) $latest['version']) + 1, 'draft', $schema, $actorId);
        }
        $wpdb->update($wpdb->prefix . 'orchestrix_forms', ['updated_at' => current_time('mysql', true)], ['id' => $formId], ['%s'], ['%d']);
        return $versionId;
    }

    public function publish(int $formId, int $actorId): int
    {
        global $wpdb;
        $versions = $wpdb->prefix . 'orchestrix_form_versions';
        $wpdb->query('START TRANSACTION');
        try {
            $draft = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$versions}
                 WHERE form_id = %d AND state = 'draft'
                 ORDER BY version DESC LIMIT 1 FOR UPDATE",
                $formId
            ), ARRAY_A);
            if (! is_array($draft)) {
                throw new DomainException(
                    __('There is no draft to publish.', 'orchestrix-forms'),
                    'draft_not_found',
                    409
                );
            }
            $now = current_time('mysql', true);
            $archived = $wpdb->query($wpdb->prepare(
                "UPDATE {$versions} SET state = 'archived' WHERE form_id = %d AND state = 'published'",
                $formId
            ));
            $published = $wpdb->update(
                $versions,
                ['state' => 'published', 'published_at' => $now, 'created_by' => $actorId],
                ['id' => (int) $draft['id']],
                ['%s', '%s', '%d'],
                ['%d']
            );
            $formUpdated = $wpdb->update($wpdb->prefix . 'orchestrix_forms', [
                'status' => 'published', 'published_version_id' => (int) $draft['id'], 'updated_at' => $now,
            ], ['id' => $formId], ['%s', '%d', '%s'], ['%d']);
            if ($archived === false || $published !== 1 || $formUpdated !== 1) {
                throw new \RuntimeException('A publish query failed.');
            }
            $wpdb->query('COMMIT');
        } catch (DomainException $error) {
            $wpdb->query('ROLLBACK');
            throw $error;
        } catch (\Throwable $error) {
            $wpdb->query('ROLLBACK');
            throw new DomainException(__('The form could not be published.', 'orchestrix-forms'), 'publish_failed', 500);
        }
        clean_post_cache($formId);
        return (int) $draft['id'];
    }

    /** @return array<string,mixed>|null */
    public function find(int $formId, bool $publishedOnly = false): ?array
    {
        global $wpdb;
        $forms = $wpdb->prefix . 'orchestrix_forms';
        $versions = $wpdb->prefix . 'orchestrix_form_versions';
        $where = $publishedOnly ? "AND f.status = 'published' AND f.published_version_id IS NOT NULL" : '';
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT f.* FROM {$forms} f WHERE f.id = %d AND f.deleted_at IS NULL {$where}",
            $formId
        ), ARRAY_A);
        if (! is_array($row)) {
            return null;
        }
        $version = $publishedOnly
            ? $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$versions} WHERE id = %d AND form_id = %d",
                (int) $row['published_version_id'],
                $formId
            ), ARRAY_A)
            : $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$versions} WHERE form_id = %d
                 ORDER BY (state = 'draft') DESC, version DESC LIMIT 1",
                $formId
            ), ARRAY_A);
        if (! is_array($version)) {
            return null;
        }
        $row = array_merge($row, [
            'version_id' => $version['id'], 'version' => $version['version'],
            'version_state' => $version['state'], 'schema_json' => $version['schema_json'],
            'schema_hash' => $version['schema_hash'],
        ]);
        $row['schema'] = json_decode((string) ($row['schema_json'] ?? '{}'), true, 64, JSON_THROW_ON_ERROR);
        $row['settings'] = json_decode((string) ($row['settings'] ?? '{}'), true, 32, JSON_THROW_ON_ERROR);
        unset($row['schema_json']);
        return $row;
    }

    /** @return list<array<string,mixed>> */
    public function list(int $page = 1, int $perPage = 20): array
    {
        global $wpdb;
        $offset = max(0, ($page - 1) * $perPage);
        $limit = min(100, max(1, $perPage));
        $table = $wpdb->prefix . 'orchestrix_forms';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT id,uuid,name,slug,status,published_version_id,created_at,updated_at FROM {$table}
             WHERE deleted_at IS NULL ORDER BY updated_at DESC, id DESC LIMIT %d OFFSET %d",
            $limit,
            $offset
        ), ARRAY_A) ?: [];
    }

    /** @return list<array<string,mixed>> */
    public function versions(int $formId): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'orchestrix_form_versions';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT id,form_id,version,state,schema_hash,created_by,created_at,published_at FROM {$table} WHERE form_id = %d ORDER BY version DESC",
            $formId
        ), ARRAY_A) ?: [];
    }

    /** @param array<string,mixed> $schema */
    private function insertVersion(int $formId, int $version, string $state, array $schema, int $actorId): int
    {
        global $wpdb;
        $json = $this->encodeSchema($schema);
        $inserted = $wpdb->insert($wpdb->prefix . 'orchestrix_form_versions', [
            'form_id' => $formId, 'version' => $version, 'state' => $state,
            'schema_json' => $json, 'schema_hash' => hash('sha256', $json),
            'created_by' => $actorId, 'created_at' => current_time('mysql', true),
        ], ['%d', '%d', '%s', '%s', '%s', '%d', '%s']);
        if ($inserted !== 1) {
            throw new DomainException(__('The form version could not be saved.', 'orchestrix-forms'), 'version_save_failed', 500);
        }
        return (int) $wpdb->insert_id;
    }

    /** @param array<string,mixed> $schema */
    private function encodeSchema(array $schema): string
    {
        $json = wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (! is_string($json) || strlen($json) > 2_000_000) {
            throw new DomainException(__('The form schema is invalid or too large.', 'orchestrix-forms'), 'invalid_schema', 422);
        }
        return $json;
    }
}
