<?php

declare(strict_types=1);

namespace Orchestrix\Forms\API;

use Orchestrix\Forms\Core\DomainException;
use Orchestrix\Forms\Fields\FieldRegistry;
use Orchestrix\Forms\Forms\FormRepository;
use Orchestrix\Forms\Submissions\SubmissionPipeline;
use Orchestrix\Forms\Submissions\SubmissionRepository;
use Orchestrix\Forms\Submissions\SubmissionValidationException;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

final class RestController
{
    public function __construct(
        private readonly FormRepository $forms,
        private readonly SubmissionRepository $submissions,
        private readonly SubmissionPipeline $pipeline,
        private readonly FieldRegistry $fields
    ) {
    }

    public function register(): void
    {
        register_rest_route('orchestrix-forms/v1', '/fields', [
            'methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'fields'],
            'permission_callback' => fn (): bool => current_user_can('orchestrix_edit_forms'),
        ]);
        register_rest_route('orchestrix-forms/v1', '/forms', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'forms'], 'permission_callback' => fn (): bool => current_user_can('orchestrix_manage_forms')],
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'createForm'], 'permission_callback' => fn (): bool => current_user_can('orchestrix_edit_forms')],
        ]);
        register_rest_route('orchestrix-forms/v1', '/forms/(?P<id>\d+)', [
            'methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'form'],
            'permission_callback' => fn (): bool => current_user_can('orchestrix_edit_forms'),
        ]);
        register_rest_route('orchestrix-forms/v1', '/forms/(?P<id>\d+)/draft', [
            'methods' => WP_REST_Server::EDITABLE, 'callback' => [$this, 'saveDraft'],
            'permission_callback' => fn (): bool => current_user_can('orchestrix_edit_forms'),
        ]);
        register_rest_route('orchestrix-forms/v1', '/forms/(?P<id>\d+)/publish', [
            'methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'publish'],
            'permission_callback' => fn (): bool => current_user_can('orchestrix_publish_forms'),
        ]);
        register_rest_route('orchestrix-forms/v1', '/forms/(?P<id>\d+)/submissions', [
            'methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'submit'],
            'permission_callback' => '__return_true',
            'args' => ['id' => ['validate_callback' => static fn (mixed $value): bool => is_numeric($value) && (int) $value > 0]],
        ]);
        register_rest_route('orchestrix-forms/v1', '/submissions', [
            'methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'submissions'],
            'permission_callback' => fn (): bool => current_user_can('orchestrix_view_entries'),
        ]);
        register_rest_route('orchestrix-forms/v1', '/submissions/(?P<id>\d+)', [
            'methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'submission'],
            'permission_callback' => fn (WP_REST_Request $request): bool => $this->canViewSubmission((int) $request['id']),
        ]);
    }

    public function fields(): WP_REST_Response
    {
        return new WP_REST_Response(array_map(static fn ($field): array => $field->getSchema(), $this->fields->all()));
    }

    /** @param WP_REST_Request<array<string,mixed>> $request */
    public function forms(WP_REST_Request $request): WP_REST_Response
    {
        return new WP_REST_Response($this->forms->list((int) ($request['page'] ?: 1), (int) ($request['per_page'] ?: 20)));
    }

    /** @param WP_REST_Request<array<string,mixed>> $request */
    public function form(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $form = $this->forms->find((int) $request['id']);
        return $form === null ? new WP_Error('not_found', __('Form not found.', 'orchestrix-forms'), ['status' => 404]) : new WP_REST_Response($form);
    }

    /** @param WP_REST_Request<array<string,mixed>> $request */
    public function createForm(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $body = $request->get_json_params();
            $name = sanitize_text_field((string) ($body['name'] ?? ''));
            if ($name === '') {
                return new WP_Error('invalid_name', __('A form name is required.', 'orchestrix-forms'), ['status' => 422]);
            }
            $id = $this->forms->create($name, is_array($body['schema'] ?? null) ? $body['schema'] : ['fields' => []], is_array($body['settings'] ?? null) ? $body['settings'] : [], get_current_user_id());
            return new WP_REST_Response(['id' => $id], 201);
        } catch (DomainException $error) {
            return $this->error($error);
        }
    }

    /** @param WP_REST_Request<array<string,mixed>> $request */
    public function saveDraft(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $body = $request->get_json_params();
            return new WP_REST_Response(['version_id' => $this->forms->saveDraft((int) $request['id'], is_array($body['schema'] ?? null) ? $body['schema'] : [], get_current_user_id())]);
        } catch (DomainException $error) {
            return $this->error($error);
        }
    }

    /** @param WP_REST_Request<array<string,mixed>> $request */
    public function publish(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            return new WP_REST_Response(['version_id' => $this->forms->publish((int) $request['id'], get_current_user_id())]);
        } catch (DomainException $error) {
            return $this->error($error);
        }
    }

    /** @param WP_REST_Request<array<string,mixed>> $request */
    public function submit(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $body = $request->get_json_params();
            if (! is_array($body)) {
                return new WP_Error('invalid_json', __('A JSON object is required.', 'orchestrix-forms'), ['status' => 400]);
            }
            return new WP_REST_Response($this->pipeline->submit((int) $request['id'], $body, $this->identity()), 201);
        } catch (SubmissionValidationException $error) {
            return new WP_Error($error->errorCode(), $error->getMessage(), ['status' => $error->getCode(), 'fields' => $error->errors()]);
        } catch (DomainException $error) {
            return $this->error($error);
        }
    }

    /** @param WP_REST_Request<array<string,mixed>> $request */
    public function submissions(WP_REST_Request $request): WP_REST_Response
    {
        return new WP_REST_Response($this->submissions->list((int) ($request['page'] ?: 1), (int) ($request['per_page'] ?: 20), $request['form_id'] ? (int) $request['form_id'] : null));
    }

    /** @param WP_REST_Request<array<string,mixed>> $request */
    public function submission(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $item = $this->submissions->find(
            (int) $request['id'],
            current_user_can('orchestrix_view_sensitive_fields')
        );
        return $item === null ? new WP_Error('not_found', __('Submission not found.', 'orchestrix-forms'), ['status' => 404]) : new WP_REST_Response($item);
    }

    private function canViewSubmission(int $id): bool
    {
        if (current_user_can('orchestrix_view_entries')) {
            return true;
        }
        $submission = $this->submissions->find($id);
        return is_array($submission) && get_current_user_id() > 0 && (int) $submission['user_id'] === get_current_user_id();
    }

    private function identity(): string
    {
        return substr((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 64);
    }

    private function error(DomainException $error): WP_Error
    {
        return new WP_Error($error->errorCode(), $error->getMessage(), ['status' => $error->getCode() ?: 400]);
    }
}
