<?php

declare(strict_types=1);

namespace Orchestrix\Forms\Fields;

final class RenderContext
{
    /** @param array<string,mixed> $field */
    public function __construct(
        public readonly array $field,
        public readonly mixed $value,
        public readonly string $formHtmlId,
        public readonly bool $readOnly = false
    ) {
    }
}
