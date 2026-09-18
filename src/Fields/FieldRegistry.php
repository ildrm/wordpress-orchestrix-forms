<?php

declare(strict_types=1);

namespace Orchestrix\Forms\Fields;

use InvalidArgumentException;

final class FieldRegistry
{
    /** @var array<string,FieldTypeInterface> */
    private array $types = [];

    public function register(FieldTypeInterface $field): void
    {
        $type = sanitize_key($field->getType());
        if ($type === '' || isset($this->types[$type])) {
            throw new InvalidArgumentException('Field types must have a unique, non-empty identifier.');
        }
        $this->types[$type] = $field;
    }

    public function has(string $type): bool
    {
        return isset($this->types[$type]);
    }

    public function get(string $type): FieldTypeInterface
    {
        if (! isset($this->types[$type])) {
            throw new InvalidArgumentException(sprintf('Unknown field type: %s', $type));
        }
        return $this->types[$type];
    }

    /** @return array<string,FieldTypeInterface> */
    public function all(): array
    {
        return $this->types;
    }
}
