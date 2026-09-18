<?php

declare(strict_types=1);

namespace Orchestrix\Forms\Validation;

final class ValidationResult
{
    /** @param array<string,list<string>> $errors */
    private function __construct(private readonly array $errors)
    {
    }

    public static function valid(): self
    {
        return new self([]);
    }

    /** @param array<string,list<string>> $errors */
    public static function invalid(array $errors): self
    {
        return new self($errors);
    }

    public function isValid(): bool
    {
        return $this->errors === [];
    }

    /** @return array<string,list<string>> */
    public function errors(): array
    {
        return $this->errors;
    }
}
