<?php

declare(strict_types=1);

namespace Orchestrix\Forms\Submissions;

use Orchestrix\Forms\AntiSpam\SpamScorer;
use Orchestrix\Forms\Calculations\ExpressionEngine;
use Orchestrix\Forms\Core\DomainException;
use Orchestrix\Forms\Forms\FormRepository;
use Orchestrix\Forms\Jobs\JobQueue;
use Orchestrix\Forms\Schema\SchemaInspector;
use Orchestrix\Forms\Security\RateLimiter;
use Orchestrix\Forms\Security\SubmissionToken;
use Orchestrix\Forms\Validation\FormValidator;

final class SubmissionPipeline
{
    public function __construct(
        private readonly FormRepository $forms,
        private readonly FormValidator $validator,
        private readonly SubmissionRepository $submissions,
        private readonly SchemaInspector $inspector,
        private readonly ExpressionEngine $calculations,
        private readonly SpamScorer $spam,
        private readonly RateLimiter $rateLimiter,
        private readonly SubmissionToken $tokens,
        private readonly JobQueue $jobs
    ) {
    }

    /**
     * @param array<string,mixed> $request
     * @return array<string,mixed>
     */
    public function submit(int $formId, array $request, string $identity): array
    {
        $form = $this->forms->find($formId, true);
        if ($form === null) {
            throw new DomainException(__('This form is unavailable.', 'orchestrix-forms'), 'form_unavailable', 404);
        }
        $versionId = (int) $form['version_id'];
        if (! $this->tokens->verify((string) ($request['token'] ?? ''), $formId, $versionId)) {
            throw new DomainException(__('The form session expired. Refresh the page and try again.', 'orchestrix-forms'), 'invalid_submission_token', 403);
        }
        if (! $this->rateLimiter->allow('submit:' . $formId, $identity, 10, 60)) {
            throw new DomainException(__('Too many submissions. Please wait and try again.', 'orchestrix-forms'), 'rate_limited', 429);
        }
        $settings = is_array($form['settings']) ? $form['settings'] : [];
        $this->enforceRestrictions($settings);
        $schema = is_array($form['schema']) ? $form['schema'] : [];
        $raw = is_array($request['values'] ?? null) ? $request['values'] : [];
        if (count($raw) > 1000) {
            throw new DomainException(__('The submission contains too many values.', 'orchestrix-forms'), 'payload_too_large', 413);
        }
        $validated = $this->validator->normalizeAndValidate($schema, $raw);
        if (! $validated['result']->isValid()) {
            throw new SubmissionValidationException($validated['result']->errors());
        }
        $values = $this->calculate($schema, $validated['values']);
        $spam = $this->spam->score($values, (int) ($request['rendered_at'] ?? 0), sanitize_text_field((string) ($request['website'] ?? '')));
        $fieldMap = [];
        foreach ($this->inspector->fields($schema) as $field) {
            $fieldMap[sanitize_key((string) ($field['key'] ?? ''))] = $field;
        }
        $submissionId = $this->submissions->create(
            $formId,
            $versionId,
            $values,
            $fieldMap,
            $spam['score'],
            ! empty($request['test']),
            (string) ($request['source_url'] ?? ''),
            $identity
        );
        if ($spam['score'] < 70) {
            $this->jobs->dispatch('submission_workflows', ['submission_id' => $submissionId, 'form_id' => $formId], 'submission:' . $submissionId);
        }
        do_action('orchestrix_forms_submission_created', $submissionId, $values, $form);
        return [
            'submission_id' => $submissionId,
            'status' => $spam['score'] >= 70 ? 'received' : 'completed',
            'message' => sanitize_text_field((string) ($settings['confirmation_message'] ?? __('Thank you. Your submission was received.', 'orchestrix-forms'))),
        ];
    }

    /**
     * @param array<string,mixed> $schema
     * @param array<string,mixed> $values
     * @return array<string,mixed>
     */
    private function calculate(array $schema, array $values): array
    {
        $expressions = [];
        foreach ((array) ($schema['calculations'] ?? []) as $calculation) {
            if (is_array($calculation) && isset($calculation['key'], $calculation['expression'])) {
                $expressions[sanitize_key((string) $calculation['key'])] = (string) $calculation['expression'];
            }
        }
        foreach ($this->calculations->order($expressions) as $key) {
            $values[$key] = $this->calculations->evaluate($expressions[$key], $values);
        }
        return $values;
    }

    /** @param array<string,mixed> $settings */
    private function enforceRestrictions(array $settings): void
    {
        $now = time();
        if (! empty($settings['start_at']) && strtotime((string) $settings['start_at']) > $now) {
            throw new DomainException(__('This form is not open yet.', 'orchestrix-forms'), 'form_not_started', 403);
        }
        if (! empty($settings['expires_at']) && strtotime((string) $settings['expires_at']) < $now) {
            throw new DomainException(__('This form is closed.', 'orchestrix-forms'), 'form_expired', 403);
        }
        if (! empty($settings['logged_in_only']) && ! is_user_logged_in()) {
            throw new DomainException(__('You must sign in to submit this form.', 'orchestrix-forms'), 'authentication_required', 401);
        }
        if (! empty($settings['logged_out_only']) && is_user_logged_in()) {
            throw new DomainException(__('This form is only available to signed-out visitors.', 'orchestrix-forms'), 'logged_out_required', 403);
        }
        if (! empty($settings['capability']) && ! current_user_can(sanitize_key((string) $settings['capability']))) {
            throw new DomainException(__('You are not allowed to submit this form.', 'orchestrix-forms'), 'submission_forbidden', 403);
        }
    }
}
