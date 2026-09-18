<?php

declare(strict_types=1);

namespace Orchestrix\Forms\Workflows;

use Orchestrix\Forms\Forms\FormRepository;
use Orchestrix\Forms\Notifications\MergeTags;
use Orchestrix\Forms\Rules\RuleEngine;
use Orchestrix\Forms\Security\UrlGuard;
use Orchestrix\Forms\Submissions\SubmissionRepository;
use Orchestrix\Forms\Webhooks\SignatureVerifier;
use RuntimeException;

final class WorkflowRunner
{
    public function __construct(
        private readonly FormRepository $forms,
        private readonly SubmissionRepository $submissions,
        private readonly RuleEngine $rules = new RuleEngine(),
        private readonly MergeTags $tags = new MergeTags(),
        private readonly UrlGuard $urls = new UrlGuard(),
        private readonly SignatureVerifier $signatures = new SignatureVerifier()
    ) {
    }

    /**
     * @param array<string,mixed> $payload
     * @param array<string,mixed> $job
     */
    public function run(array $payload, array $job): void
    {
        $submission = $this->submissions->find((int) ($payload['submission_id'] ?? 0));
        $form = $this->forms->find((int) ($payload['form_id'] ?? 0), true);
        if ($submission === null || $form === null) {
            throw new RuntimeException('Workflow input no longer exists.');
        }
        $context = ['submission' => $submission, 'values' => $submission['values'] ?? [], 'form' => $form];
        foreach ((array) ($form['schema']['workflows'] ?? []) as $workflow) {
            if (! is_array($workflow) || ! empty($workflow['disabled'])) {
                continue;
            }
            if (isset($workflow['conditions']) && is_array($workflow['conditions']) && ! $this->rules->evaluate($workflow['conditions'], $context)) {
                continue;
            }
            foreach ((array) ($workflow['actions'] ?? []) as $action) {
                if (is_array($action)) {
                    $this->action($action, $context, (string) ($job['uuid'] ?? ''));
                }
            }
        }
    }

    /**
     * @param array<string,mixed> $action
     * @param array<string,mixed> $context
     */
    private function action(array $action, array $context, string $jobUuid): void
    {
        $type = sanitize_key((string) ($action['type'] ?? ''));
        if ($type === 'email') {
            $to = sanitize_email($this->tags->render((string) ($action['to'] ?? ''), $context, 'header'));
            $subject = $this->tags->render((string) ($action['subject'] ?? ''), $context, 'header');
            $body = $this->tags->render((string) ($action['body'] ?? ''), $context, 'html');
            if ($to === '' || ! wp_mail($to, $subject, $body, ['Content-Type: text/html; charset=UTF-8'])) {
                throw new RuntimeException('Notification delivery failed.');
            }
            return;
        }
        if ($type === 'webhook') {
            $url = esc_url_raw((string) ($action['url'] ?? ''), ['https']);
            $allow = array_values(array_filter(array_map('sanitize_text_field', (array) ($action['allowed_hosts'] ?? []))));
            if (! $this->urls->isAllowed($url, $allow)) {
                throw new RuntimeException('Webhook destination was blocked.');
            }
            $body = wp_json_encode($context) ?: '{}';
            $timestamp = time();
            $secret = (string) ($action['secret'] ?? '');
            $response = wp_safe_remote_post($url, [
                'timeout' => min(30, max(2, (int) ($action['timeout'] ?? 10))),
                'redirection' => 0,
                'headers' => ['Content-Type' => 'application/json', 'X-Orchestrix-Timestamp' => (string) $timestamp, 'X-Orchestrix-Signature' => $this->signatures->sign($body, $secret, $timestamp), 'Idempotency-Key' => $jobUuid],
                'body' => $body, 'data_format' => 'body',
            ]);
            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) >= 300) {
                throw new RuntimeException('Webhook delivery failed.');
            }
            return;
        }
        do_action('orchestrix_forms_workflow_action_' . $type, $action, $context, $jobUuid);
    }
}
