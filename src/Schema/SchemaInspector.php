<?php

declare(strict_types=1);

namespace Orchestrix\Forms\Schema;

final class SchemaInspector
{
    /**
     * @param array<string,mixed> $schema
     * @return list<array<string,mixed>>
     */
    public function fields(array $schema): array
    {
        $result = [];
        $walk = function (array $nodes) use (&$walk, &$result): void {
            foreach ($nodes as $node) {
                if (! is_array($node)) {
                    continue;
                }
                if (isset($node['type'], $node['key'])) {
                    $result[] = $node;
                }
                foreach (['children', 'fields', 'layouts'] as $childKey) {
                    if (isset($node[$childKey]) && is_array($node[$childKey])) {
                        $walk($node[$childKey]);
                    }
                }
            }
        };
        $walk(is_array($schema['fields'] ?? null) ? $schema['fields'] : []);
        return $result;
    }

    /**
     * @param array<string,mixed> $schema
     * @return list<string>
     */
    public function features(array $schema): array
    {
        $features = [];
        $map = [
            'repeater' => 'repeater', 'nested_repeater' => 'repeater', 'flexible_layout' => 'repeater',
            'signature' => 'signature', 'drawing' => 'signature', 'map_location' => 'maps',
            'geolocation' => 'maps', 'autocomplete_address' => 'maps', 'payment_method' => 'payments',
            'product' => 'payments', 'likert' => 'survey', 'matrix' => 'survey', 'nps' => 'survey',
            'multiple_choice' => 'quiz', 'multiple_answer' => 'quiz', 'weighted_choice' => 'quiz',
        ];
        foreach ($this->fields($schema) as $field) {
            $type = (string) $field['type'];
            if (isset($map[$type])) {
                $features[] = $map[$type];
            }
        }
        if (! empty($schema['rules'])) {
            $features[] = 'rules';
        }
        if (! empty($schema['calculations'])) {
            $features[] = 'calculations';
        }
        return array_values(array_unique($features));
    }
}
