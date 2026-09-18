<?php

declare(strict_types=1);

namespace Orchestrix\Forms\Migrations;

final class Schema
{
    public const VERSION = '1.0.0';

    public function migrate(): void
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();
        $p = $wpdb->prefix . 'orchestrix_';

        $sql = [
            "CREATE TABLE {$p}forms (
                id bigint unsigned NOT NULL AUTO_INCREMENT,
                uuid char(36) NOT NULL,
                name varchar(190) NOT NULL,
                slug varchar(190) NOT NULL,
                status varchar(20) NOT NULL DEFAULT 'draft',
                published_version_id bigint unsigned NULL,
                settings longtext NOT NULL,
                created_by bigint unsigned NOT NULL DEFAULT 0,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                deleted_at datetime NULL,
                PRIMARY KEY (id), UNIQUE KEY uuid (uuid), UNIQUE KEY slug (slug),
                KEY status_updated (status,updated_at)
            ) {$charset};",
            "CREATE TABLE {$p}form_versions (
                id bigint unsigned NOT NULL AUTO_INCREMENT,
                form_id bigint unsigned NOT NULL,
                version int unsigned NOT NULL,
                state varchar(20) NOT NULL DEFAULT 'draft',
                schema_json longtext NOT NULL,
                schema_hash char(64) NOT NULL,
                created_by bigint unsigned NOT NULL DEFAULT 0,
                created_at datetime NOT NULL,
                published_at datetime NULL,
                PRIMARY KEY (id), UNIQUE KEY form_version (form_id,version),
                KEY form_state (form_id,state)
            ) {$charset};",
            "CREATE TABLE {$p}submissions (
                id bigint unsigned NOT NULL AUTO_INCREMENT,
                uuid char(36) NOT NULL,
                form_id bigint unsigned NOT NULL,
                form_version_id bigint unsigned NOT NULL,
                user_id bigint unsigned NULL,
                status varchar(20) NOT NULL DEFAULT 'completed',
                is_test tinyint(1) NOT NULL DEFAULT 0,
                spam_score smallint unsigned NOT NULL DEFAULT 0,
                source_url text NULL,
                ip_hash char(64) NULL,
                user_agent_hash char(64) NULL,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY (id), UNIQUE KEY uuid (uuid),
                KEY form_created (form_id,created_at), KEY user_form (user_id,form_id),
                KEY status_created (status,created_at), KEY version_id (form_version_id)
            ) {$charset};",
            "CREATE TABLE {$p}submission_values (
                id bigint unsigned NOT NULL AUTO_INCREMENT,
                submission_id bigint unsigned NOT NULL,
                form_id bigint unsigned NOT NULL,
                field_key varchar(190) NOT NULL,
                value_text longtext NULL,
                value_number decimal(30,10) NULL,
                value_date datetime NULL,
                privacy varchar(20) NOT NULL DEFAULT 'personal',
                value_hash char(64) NULL,
                unique_scope char(64) NULL,
                PRIMARY KEY (id), UNIQUE KEY submission_field (submission_id,field_key),
                UNIQUE KEY strict_unique (form_id,field_key,value_hash,unique_scope),
                KEY field_hash (field_key,value_hash), KEY field_number (field_key,value_number),
                KEY field_date (field_key,value_date)
            ) {$charset};",
            "CREATE TABLE {$p}submission_revisions (
                id bigint unsigned NOT NULL AUTO_INCREMENT,
                submission_id bigint unsigned NOT NULL,
                revision int unsigned NOT NULL,
                values_json longtext NOT NULL,
                actor_id bigint unsigned NOT NULL DEFAULT 0,
                reason varchar(255) NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY (id), UNIQUE KEY submission_revision (submission_id,revision)
            ) {$charset};",
            "CREATE TABLE {$p}drafts (
                id bigint unsigned NOT NULL AUTO_INCREMENT,
                form_id bigint unsigned NOT NULL,
                form_version_id bigint unsigned NOT NULL,
                user_id bigint unsigned NULL,
                token_hash char(64) NULL,
                values_json longtext NOT NULL,
                status varchar(20) NOT NULL DEFAULT 'draft',
                expires_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY (id), UNIQUE KEY token_hash (token_hash),
                KEY expiry (status,expires_at), KEY user_form (user_id,form_id)
            ) {$charset};",
            "CREATE TABLE {$p}jobs (
                id bigint unsigned NOT NULL AUTO_INCREMENT,
                uuid char(36) NOT NULL,
                type varchar(100) NOT NULL,
                payload_json longtext NOT NULL,
                status varchar(20) NOT NULL DEFAULT 'pending',
                attempts smallint unsigned NOT NULL DEFAULT 0,
                max_attempts smallint unsigned NOT NULL DEFAULT 5,
                available_at datetime NOT NULL,
                locked_at datetime NULL,
                idempotency_key varchar(190) NULL,
                last_error text NULL,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY (id), UNIQUE KEY uuid (uuid), UNIQUE KEY idempotency (idempotency_key),
                KEY ready (status,available_at)
            ) {$charset};",
            "CREATE TABLE {$p}payments (
                id bigint unsigned NOT NULL AUTO_INCREMENT,
                uuid char(36) NOT NULL,
                submission_id bigint unsigned NOT NULL,
                gateway varchar(50) NOT NULL,
                gateway_reference varchar(190) NULL,
                state varchar(30) NOT NULL DEFAULT 'pending',
                amount_minor bigint NOT NULL DEFAULT 0,
                currency char(3) NOT NULL,
                refunded_minor bigint NOT NULL DEFAULT 0,
                idempotency_key varchar(190) NOT NULL,
                metadata_json longtext NOT NULL,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY (id), UNIQUE KEY uuid (uuid), UNIQUE KEY idempotency (idempotency_key),
                KEY submission_id (submission_id), KEY gateway_reference (gateway,gateway_reference)
            ) {$charset};",
            "CREATE TABLE {$p}workflow_runs (
                id bigint unsigned NOT NULL AUTO_INCREMENT,
                workflow_id bigint unsigned NOT NULL,
                submission_id bigint unsigned NOT NULL,
                status varchar(20) NOT NULL,
                step_key varchar(190) NULL,
                context_json longtext NOT NULL,
                error text NULL,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY (id), KEY submission_status (submission_id,status),
                KEY workflow_status (workflow_id,status)
            ) {$charset};",
            "CREATE TABLE {$p}audit_log (
                id bigint unsigned NOT NULL AUTO_INCREMENT,
                actor_id bigint unsigned NULL,
                action varchar(100) NOT NULL,
                object_type varchar(50) NOT NULL,
                object_id varchar(190) NOT NULL,
                metadata_json longtext NOT NULL,
                ip_hash char(64) NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY (id), KEY object_events (object_type,object_id,created_at),
                KEY actor_created (actor_id,created_at)
            ) {$charset};",
            "CREATE TABLE {$p}idempotency (
                id bigint unsigned NOT NULL AUTO_INCREMENT,
                scope varchar(100) NOT NULL,
                idempotency_key varchar(190) NOT NULL,
                response_json longtext NULL,
                created_at datetime NOT NULL,
                expires_at datetime NOT NULL,
                PRIMARY KEY (id), UNIQUE KEY scope_key (scope,idempotency_key), KEY expiry (expires_at)
            ) {$charset};",
            "CREATE TABLE {$p}capacity (
                id bigint unsigned NOT NULL AUTO_INCREMENT,
                resource_key varchar(190) NOT NULL,
                slot_key varchar(190) NOT NULL,
                capacity int unsigned NOT NULL,
                reserved int unsigned NOT NULL DEFAULT 0,
                updated_at datetime NOT NULL,
                PRIMARY KEY (id), UNIQUE KEY resource_slot (resource_key,slot_key)
            ) {$charset};",
            "CREATE TABLE {$p}analytics_daily (
                id bigint unsigned NOT NULL AUTO_INCREMENT,
                form_id bigint unsigned NOT NULL,
                event_date date NOT NULL,
                event_type varchar(50) NOT NULL,
                dimension_hash char(64) NOT NULL DEFAULT '',
                event_count bigint unsigned NOT NULL DEFAULT 0,
                total_value decimal(30,6) NOT NULL DEFAULT 0,
                PRIMARY KEY (id), UNIQUE KEY aggregate (form_id,event_date,event_type,dimension_hash)
            ) {$charset};",
        ];

        foreach ($sql as $statement) {
            dbDelta($statement);
        }
        update_option('orchestrix_forms_db_version', self::VERSION, false);
        add_option('orchestrix_forms_uninstall_mode', 'preserve', '', false);
    }
}
