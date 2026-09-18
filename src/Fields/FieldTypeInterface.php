<?php

declare(strict_types=1);

namespace Orchestrix\Forms\Fields;

use Orchestrix\Forms\Validation\ValidationResult;

interface FieldTypeInterface
{
    public function getType(): string;

    /** @return array<string,mixed> */
    public function getSchema(): array;

    public function sanitize(mixed $value, FieldContext $context): mixed;

    public function validate(mixed $value, FieldContext $context): ValidationResult;

    public function serialize(mixed $value): mixed;

    public function deserialize(mixed $value): mixed;

    public function render(RenderContext $context): string;
}
