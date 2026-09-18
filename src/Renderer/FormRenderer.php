<?php

declare(strict_types=1);

namespace Orchestrix\Forms\Renderer;

use Orchestrix\Forms\Fields\FieldRegistry;
use Orchestrix\Forms\Fields\RenderContext;
use Orchestrix\Forms\Forms\FormRepository;
use Orchestrix\Forms\Schema\SchemaInspector;
use Orchestrix\Forms\Security\SubmissionToken;

final class FormRenderer
{
    public function __construct(
        private readonly FormRepository $forms,
        private readonly FieldRegistry $fields,
        private readonly SchemaInspector $inspector,
        private readonly SubmissionToken $tokens
    ) {
    }

    /** @param array<string,mixed> $attributes */
    public function render(int $formId, array $attributes = []): string
    {
        $form = $this->forms->find($formId, true);
        if ($form === null) {
            return current_user_can('orchestrix_edit_forms') ? '<p class="orchestrix-notice">' . esc_html__('Form not found or not published.', 'orchestrix-forms') . '</p>' : '';
        }
        $schema = is_array($form['schema']) ? $form['schema'] : [];
        $htmlId = 'orchestrix-form-' . $formId . '-' . wp_unique_id();
        $fieldsHtml = '';
        foreach ($this->inspector->fields($schema) as $field) {
            $type = sanitize_key((string) ($field['type'] ?? ''));
            if ($this->fields->has($type)) {
                $fieldsHtml .= $this->fields->get($type)->render(new RenderContext($field, $field['default'] ?? '', $htmlId));
            }
        }
        $this->enqueueAssets($schema);
        $config = [
            'endpoint' => esc_url_raw(rest_url('orchestrix-forms/v1/forms/' . $formId . '/submissions')),
            'features' => $this->inspector->features($schema), 'rules' => $schema['rules'] ?? [], 'calculations' => $schema['calculations'] ?? [],
        ];
        return sprintf(
            '<form id="%1$s" class="orchestrix-form" method="post" action="%2$s" data-orchestrix-form="%3$d" data-config="%4$s" novalidate><div class="orchestrix-error-summary" role="alert" aria-live="assertive" tabindex="-1" hidden></div><input type="hidden" name="action" value="orchestrix_submit"><input type="hidden" name="form_id" value="%3$d"><input type="hidden" name="orchestrix_token" value="%5$s"><input type="hidden" name="orchestrix_rendered_at" value="%6$d"><div class="orchestrix-hp" aria-hidden="true"><label>Website<input name="website" type="text" tabindex="-1" autocomplete="off"></label></div>%7$s<button type="submit" class="orchestrix-submit">%8$s</button><div class="orchestrix-status" role="status" aria-live="polite"></div></form>',
            esc_attr($htmlId),
            esc_url(admin_url('admin-post.php')),
            $formId,
            esc_attr(wp_json_encode($config) ?: '{}'),
            esc_attr($this->tokens->issue($formId, (int) $form['version_id'])),
            time(),
            $fieldsHtml,
            esc_html((string) ($schema['submit_label'] ?? __('Submit', 'orchestrix-forms')))
        );
    }

    /** @param array<string,mixed> $schema */
    private function enqueueAssets(array $schema): void
    {
        wp_enqueue_style('orchestrix-forms', ORCHESTRIX_FORMS_URL . 'assets/dist/frontend.css', [], ORCHESTRIX_FORMS_VERSION);
        wp_enqueue_script('orchestrix-forms', ORCHESTRIX_FORMS_URL . 'assets/dist/frontend.js', [], ORCHESTRIX_FORMS_VERSION, true);
        foreach ($this->inspector->features($schema) as $feature) {
            $path = 'assets/dist/features/' . sanitize_file_name($feature) . '.js';
            if (is_readable(ORCHESTRIX_FORMS_DIR . $path)) {
                wp_enqueue_script('orchestrix-forms-' . $feature, ORCHESTRIX_FORMS_URL . $path, ['orchestrix-forms'], ORCHESTRIX_FORMS_VERSION, true);
            }
        }
    }
}
