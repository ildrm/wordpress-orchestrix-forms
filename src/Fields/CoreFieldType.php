<?php

declare(strict_types=1);

namespace Orchestrix\Forms\Fields;

use Orchestrix\Forms\Validation\ValidationResult;

final class CoreFieldType implements FieldTypeInterface
{
    /** @param array<string,mixed> $definition */
    public function __construct(private readonly string $type, private readonly array $definition)
    {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSchema(): array
    {
        return $this->definition + [
            'type' => $this->type,
            'category' => 'basic',
            'input' => 'text',
            'privacy' => 'personal',
            'supports' => ['label', 'description', 'default', 'required', 'validation', 'logic', 'css', 'a11y'],
        ];
    }

    public function sanitize(mixed $value, FieldContext $context): mixed
    {
        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->sanitize($item, $context), array_values($value));
        }
        if ($value === null) {
            return null;
        }
        $input = (string) ($this->definition['input'] ?? 'text');
        return match ($input) {
            'email' => sanitize_email((string) $value),
            'url' => esc_url_raw((string) $value, ['http', 'https']),
            'integer' => filter_var($value, FILTER_VALIDATE_INT) !== false ? (int) $value : null,
            'number', 'range' => is_numeric($value) ? (float) $value : null,
            'textarea', 'richtext' => sanitize_textarea_field((string) $value),
            'html', 'structural' => '',
            'password' => substr((string) $value, 0, 4096),
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            default => sanitize_text_field((string) $value),
        };
    }

    public function validate(mixed $value, FieldContext $context): ValidationResult
    {
        $field = $context->field;
        $key = (string) ($field['key'] ?? $this->type);
        $label = (string) ($field['label'] ?? $key);
        $empty = $value === null || $value === '' || $value === [];
        $errors = [];
        if (! empty($field['required']) && $empty) {
            $errors[] = sprintf(__('%s is required.', 'orchestrix-forms'), $label);
        }
        if (! $empty) {
            $input = (string) ($this->definition['input'] ?? 'text');
            if ($input === 'email' && ! is_email((string) $value)) {
                $errors[] = sprintf(__('%s must be a valid email address.', 'orchestrix-forms'), $label);
            }
            if ($input === 'url' && filter_var($value, FILTER_VALIDATE_URL) === false) {
                $errors[] = sprintf(__('%s must be a valid URL.', 'orchestrix-forms'), $label);
            }
            if (in_array($input, ['number', 'integer', 'range'], true) && ! is_numeric($value)) {
                $errors[] = sprintf(__('%s must be a number.', 'orchestrix-forms'), $label);
            }
            if (isset($field['min']) && is_numeric($value) && (float) $value < (float) $field['min']) {
                $errors[] = sprintf(__('%1$s must be at least %2$s.', 'orchestrix-forms'), $label, (string) $field['min']);
            }
            if (isset($field['max']) && is_numeric($value) && (float) $value > (float) $field['max']) {
                $errors[] = sprintf(__('%1$s must be no more than %2$s.', 'orchestrix-forms'), $label, (string) $field['max']);
            }
            if (isset($field['pattern']) && is_string($field['pattern']) && strlen($field['pattern']) <= 200) {
                $pattern = '~' . str_replace('~', '\\~', $field['pattern']) . '~uD';
                if (@preg_match($pattern, (string) $value) !== 1) {
                    $errors[] = sprintf(__('%s has an invalid format.', 'orchestrix-forms'), $label);
                }
            }
        }
        return $errors === [] ? ValidationResult::valid() : ValidationResult::invalid([$key => $errors]);
    }

    public function serialize(mixed $value): mixed
    {
        return $value;
    }

    public function deserialize(mixed $value): mixed
    {
        return $value;
    }

    public function render(RenderContext $context): string
    {
        $field = $context->field;
        $key = sanitize_key((string) ($field['key'] ?? 'field'));
        $id = $context->formHtmlId . '-' . $key;
        $label = esc_html((string) ($field['label'] ?? ucfirst($key)));
        $description = (string) ($field['description'] ?? '');
        $required = ! empty($field['required']);
        $input = (string) ($this->definition['input'] ?? 'text');
        $describedBy = $description !== '' ? $id . '-description' : '';
        $attributes = sprintf(
            ' id="%s" name="orchestrix[%s]"%s%s%s%s',
            esc_attr($id),
            esc_attr($key),
            $required ? ' required aria-required="true"' : '',
            $describedBy !== '' ? ' aria-describedby="' . esc_attr($describedBy) . '"' : '',
            $context->readOnly ? ' disabled' : '',
            isset($field['placeholder']) ? ' placeholder="' . esc_attr((string) $field['placeholder']) . '"' : ''
        );
        $value = is_scalar($context->value) ? (string) $context->value : '';
        $control = $this->renderControl($input, $attributes, $value, $field);
        if ($input === 'structural' || $input === 'html') {
            return $control;
        }
        return sprintf(
            '<div class="orchestrix-field orchestrix-field--%1$s"><label for="%2$s">%3$s%4$s</label>%5$s%6$s</div>',
            esc_attr($this->type),
            esc_attr($id),
            $label,
            $required ? '<span aria-hidden="true" class="orchestrix-required"> *</span>' : '',
            $control,
            $description !== '' ? '<p id="' . esc_attr($describedBy) . '" class="orchestrix-description">' . esc_html($description) . '</p>' : ''
        );
    }

    /** @param array<string,mixed> $field */
    private function renderControl(string $input, string $attributes, string $value, array $field): string
    {
        if ($input === 'textarea' || $input === 'richtext') {
            return '<textarea' . $attributes . '>' . esc_textarea($value) . '</textarea>';
        }
        if (in_array($input, ['select', 'multiselect'], true)) {
            $multiple = $input === 'multiselect' ? ' multiple' : '';
            $options = '';
            foreach ((array) ($field['options'] ?? []) as $option) {
                $optionValue = is_array($option) ? (string) ($option['value'] ?? '') : (string) $option;
                $optionLabel = is_array($option) ? (string) ($option['label'] ?? $optionValue) : $optionValue;
                $options .= sprintf('<option value="%s"%s>%s</option>', esc_attr($optionValue), selected($value, $optionValue, false), esc_html($optionLabel));
            }
            return '<select' . $attributes . $multiple . '>' . $options . '</select>';
        }
        if (in_array($input, ['radio', 'checkboxes'], true)) {
            $items = '';
            preg_match('/id="([^"]+)"/', $attributes, $idMatch);
            $baseId = $idMatch[1] ?? 'orchestrix-choice';
            $key = sanitize_key((string) ($field['key'] ?? 'field'));
            foreach ((array) ($field['options'] ?? []) as $index => $option) {
                $optionValue = is_array($option) ? (string) ($option['value'] ?? '') : (string) $option;
                $optionLabel = is_array($option) ? (string) ($option['label'] ?? $optionValue) : $optionValue;
                $choiceId = $baseId . '-' . (int) $index;
                $items .= '<label for="' . esc_attr($choiceId) . '"><input id="' . esc_attr($choiceId) . '" type="'
                    . ($input === 'radio' ? 'radio' : 'checkbox') . '" name="orchestrix[' . esc_attr($key) . ']'
                    . ($input === 'checkboxes' ? '[]' : '') . '" value="' . esc_attr($optionValue) . '"'
                    . (! empty($field['required']) ? ' required' : '') . '> ' . esc_html($optionLabel) . '</label>';
            }
            return '<fieldset><legend class="screen-reader-text">' . esc_html((string) ($field['label'] ?? '')) . '</legend>' . $items . '</fieldset>';
        }
        if ($input === 'structural') {
            return '<div class="orchestrix-structure" role="presentation">' . esc_html((string) ($field['content'] ?? $field['label'] ?? '')) . '</div>';
        }
        if ($input === 'html') {
            return '<div class="orchestrix-content">' . wp_kses_post((string) ($field['content'] ?? '')) . '</div>';
        }
        $htmlType = in_array($input, ['email', 'url', 'password', 'date', 'time', 'datetime-local', 'number', 'range', 'color', 'tel', 'hidden', 'file'], true) ? $input : 'text';
        $accept = $htmlType === 'file' && isset($field['accept']) ? ' accept="' . esc_attr((string) $field['accept']) . '"' : '';
        $capture = $htmlType === 'file' && ! empty($field['capture']) ? ' capture="environment"' : '';
        return '<input type="' . esc_attr($htmlType) . '"' . $attributes . $accept . $capture . ($htmlType === 'file' ? '' : ' value="' . esc_attr($value) . '"') . '>';
    }
}
