<?php

declare(strict_types=1);

namespace Orchestrix\Forms\Developer;

use Orchestrix\Forms\Forms\FormRepository;
use Orchestrix\Forms\Jobs\JobQueue;
use Orchestrix\Forms\Submissions\SubmissionRepository;

final class CliCommand
{
    public function __construct(
        private readonly FormRepository $forms,
        private readonly SubmissionRepository $submissions,
        private readonly JobQueue $jobs
    ) {
    }

    /** @param list<string> $args @param array<string,string> $assoc */
    public function forms(array $args, array $assoc): void
    {
        $action = $args[0] ?? 'list';
        if ($action !== 'list') {
            \WP_CLI::error('Supported action: forms list');
        }
        \WP_CLI\Utils\format_items('table', $this->forms->list(1, (int) ($assoc['limit'] ?? 100)), ['id', 'name', 'status', 'updated_at']);
    }

    /** @param list<string> $args */
    public function queue(array $args): void
    {
        $action = $args[0] ?? 'status';
        if ($action === 'run') {
            \WP_CLI::success(sprintf('Processed %d jobs.', $this->jobs->process(100)));
            return;
        }
        global $wpdb;
        $rows = $wpdb->get_results("SELECT status,COUNT(*) AS count FROM {$wpdb->prefix}orchestrix_jobs GROUP BY status", ARRAY_A) ?: [];
        \WP_CLI\Utils\format_items('table', $rows, ['status', 'count']);
    }

    public function diagnostics(): void
    {
        \WP_CLI::log('Plugin: ' . ORCHESTRIX_FORMS_VERSION);
        \WP_CLI::log('Database: ' . (string) get_option('orchestrix_forms_db_version', 'not installed'));
        \WP_CLI::success('Diagnostics complete.');
    }
}
