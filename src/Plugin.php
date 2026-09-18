<?php

declare(strict_types=1);

namespace Orchestrix\Forms;

use Orchestrix\Forms\Admin\Admin;
use Orchestrix\Forms\AntiSpam\SpamScorer;
use Orchestrix\Forms\API\RestController;
use Orchestrix\Forms\Calculations\ExpressionEngine;
use Orchestrix\Forms\Core\DomainException;
use Orchestrix\Forms\Fields\CoreFieldCatalog;
use Orchestrix\Forms\Fields\FieldRegistry;
use Orchestrix\Forms\Forms\FormRepository;
use Orchestrix\Forms\Jobs\JobQueue;
use Orchestrix\Forms\Renderer\FormRenderer;
use Orchestrix\Forms\Rules\RuleEngine;
use Orchestrix\Forms\Schema\SchemaInspector;
use Orchestrix\Forms\Security\RateLimiter;
use Orchestrix\Forms\Security\SubmissionToken;
use Orchestrix\Forms\Submissions\SubmissionPipeline;
use Orchestrix\Forms\Submissions\SubmissionRepository;
use Orchestrix\Forms\Validation\FormValidator;
use Orchestrix\Forms\Workflows\WorkflowRunner;

final class Plugin
{
    private static ?self $instance = null;
    private bool $booted = false;
    private FormRenderer $renderer;
    private SubmissionPipeline $pipeline;
    private JobQueue $jobs;

    private function __construct()
    {
    }

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }
        $this->booted = true;
        load_plugin_textdomain('orchestrix-forms', false, dirname(plugin_basename(ORCHESTRIX_FORMS_FILE)) . '/languages');

        $fields = new FieldRegistry();
        CoreFieldCatalog::register($fields);
        do_action('orchestrix_forms_register_fields', $fields);
        $forms = new FormRepository();
        $submissions = new SubmissionRepository();
        $inspector = new SchemaInspector();
        $rules = new RuleEngine();
        $validator = new FormValidator($fields, $inspector, $rules);
        $tokens = new SubmissionToken();
        $this->jobs = new JobQueue();
        $this->pipeline = new SubmissionPipeline(
            $forms,
            $validator,
            $submissions,
            $inspector,
            new ExpressionEngine(),
            new SpamScorer(),
            new RateLimiter(),
            $tokens,
            $this->jobs
        );
        $this->renderer = new FormRenderer($forms, $fields, $inspector, $tokens);
        $workflowRunner = new WorkflowRunner($forms, $submissions);

        $api = new RestController($forms, $submissions, $this->pipeline, $fields);
        add_action('rest_api_init', [$api, 'register']);
        add_shortcode('orchestrix_form', function (array $attributes): string {
            $attributes = shortcode_atts(['id' => 0], $attributes, 'orchestrix_form');
            return $this->renderer->render((int) $attributes['id'], $attributes);
        });
        add_action('init', [$this, 'registerBlock']);
        add_action('admin_post_nopriv_orchestrix_submit', [$this, 'nonAjaxSubmit']);
        add_action('admin_post_orchestrix_submit', [$this, 'nonAjaxSubmit']);
        add_action('orchestrix_forms_process_jobs', fn (): int => $this->jobs->process(20));
        add_action('orchestrix_forms_job_submission_workflows', [$workflowRunner, 'run'], 10, 2);
        add_action('orchestrix_forms_cleanup', [$this, 'cleanup']);

        if (is_admin()) {
            $admin = new Admin();
            add_action('admin_menu', [$admin, 'registerMenu']);
            add_action('admin_enqueue_scripts', [$admin, 'enqueue']);
        }
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::add_command('orchestrix', new \Orchestrix\Forms\Developer\CliCommand($forms, $submissions, $this->jobs));
        }
    }

    public function renderer(): FormRenderer
    {
        return $this->renderer;
    }

    public function registerBlock(): void
    {
        wp_register_script(
            'orchestrix-form-block',
            ORCHESTRIX_FORMS_URL . 'assets/dist/block.js',
            ['wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render', 'wp-i18n'],
            ORCHESTRIX_FORMS_VERSION,
            true
        );
        register_block_type('orchestrix-forms/form', [
            'api_version' => '3',
            'attributes' => ['formId' => ['type' => 'integer', 'default' => 0]],
            'render_callback' => fn (array $attributes): string => $this->renderer->render((int) ($attributes['formId'] ?? 0), $attributes),
            'editor_script' => 'orchestrix-form-block',
        ]);
    }

    public function nonAjaxSubmit(): void
    {
        $redirect = wp_get_referer() ?: home_url('/');
        try {
            $raw = isset($_POST['orchestrix']) && is_array($_POST['orchestrix']) ? wp_unslash($_POST['orchestrix']) : [];
            $this->pipeline->submit((int) ($_POST['form_id'] ?? 0), [
                'values' => $raw, 'token' => sanitize_text_field(wp_unslash((string) ($_POST['orchestrix_token'] ?? ''))),
                'rendered_at' => (int) ($_POST['orchestrix_rendered_at'] ?? 0),
                'website' => sanitize_text_field(wp_unslash((string) ($_POST['website'] ?? ''))),
                'source_url' => $redirect,
            ], substr((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 64));
            $redirect = add_query_arg('orchestrix_status', 'success', $redirect);
        } catch (DomainException $error) {
            $redirect = add_query_arg('orchestrix_status', rawurlencode($error->errorCode()), $redirect);
        }
        wp_safe_redirect($redirect, 303);
        exit;
    }

    public function cleanup(): void
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}orchestrix_idempotency WHERE expires_at < %s LIMIT 1000", current_time('mysql', true)));
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}orchestrix_drafts WHERE expires_at < %s LIMIT 1000", current_time('mysql', true)));
    }
}
