<?php

declare(strict_types=1);

namespace Orchestrix\Forms\Fields;

final class FieldContext
{
    /**
     * @param array<string,mixed> $field
     * @param array<string,mixed> $values
     */
    public function __construct(
        public readonly array $field,
        public readonly array $values = [],
        public readonly string $locale = 'en_US'
    ) {
    }
}
