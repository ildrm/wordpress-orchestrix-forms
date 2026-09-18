<?php

declare(strict_types=1);

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

if (get_option('orchestrix_forms_uninstall_mode', 'preserve') !== 'remove_all') {
    return;
}

global $wpdb;
$suffixes = [
    'forms', 'form_versions', 'submissions', 'submission_values', 'submission_revisions', 'drafts',
    'jobs', 'payments', 'workflow_runs', 'audit_log', 'idempotency', 'capacity', 'analytics_daily',
];
foreach ($suffixes as $suffix) {
    $table = $wpdb->prefix . 'orchestrix_' . $suffix;
    $wpdb->query("DROP TABLE IF EXISTS `{$table}`"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- internal allowlist.
}
delete_option('orchestrix_forms_db_version');
delete_option('orchestrix_forms_uninstall_mode');
