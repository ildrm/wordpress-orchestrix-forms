<?php

declare(strict_types=1);

namespace Orchestrix\Forms\Validation;

use Orchestrix\Forms\Fields\FieldContext;
use Orchestrix\Forms\Fields\FieldRegistry;
use Orchestrix\Forms\Rules\RuleEngine;
use Orchestrix\Forms\Schema\SchemaInspector;

final class FormValidator
{
    public function __construct(
        private readonly FieldRegistry $fields,
        private readonly SchemaInspector $inspector,
        private readonly RuleEngine $rules
    ) {
    }

    /**
     * @param array<string,mixed> $schema
     * @param array<string,mixed> $raw
     * @return array{values:array<string,mixed>,result:ValidationResult}
     */
    public function normalizeAndValidate(array $schema, array $raw): array
    {
        $values = [];
        $errors = [];
        foreach ($this->inspector->fields($schema) as $field) {
            $key = sanitize_key((string) ($field['key'] ?? ''));
            $type = sanitize_key((string) ($field['type'] ?? ''));
            if ($key === '' || ! $this->fields->has($type)) {
                continue;
            }
            $visible = true;
            if (isset($field['visibility_rule']) && is_array($field['visibility_rule'])) {
                $visible = $this->rules->evaluate($field['visibility_rule'], $raw + $values);
            }
            if (! $visible) {
                continue;
            }
            $definition = $field;
            if (isset($field['required_rule']) && is_array($field['required_rule'])) {
                $definition['required'] = $this->rules->evaluate($field['required_rule'], $raw + $values);
            }
            $handler = $this->fields->get($type);
            $context = new FieldContext($definition, $values + $raw, determine_locale());
            $value = $handler->sanitize($raw[$key] ?? ($field['default'] ?? null), $context);
            $values[$key] = $value;
            $result = $handler->validate($value, $context);
            foreach ($result->errors() as $fieldKey => $fieldErrors) {
                $errors[$fieldKey] = array_merge($errors[$fieldKey] ?? [], $fieldErrors);
            }
        }
        /** @var array<string,list<string>> $errors */
        $errors = apply_filters('orchestrix_forms_validation_errors', $errors, $values, $schema);
        return ['values' => $values, 'result' => $errors === [] ? ValidationResult::valid() : ValidationResult::invalid($errors)];
    }
}
