<?php

declare(strict_types=1);

namespace Orchestrix\Forms\Submissions;

use Orchestrix\Forms\Core\DomainException;

final class SubmissionValidationException extends DomainException
{
    /** @param array<string,list<string>> $errors */
    public function __construct(private readonly array $errors)
    {
        parent::__construct(__('Please correct the highlighted fields.', 'orchestrix-forms'), 'validation_failed', 422);
    }

    /** @return array<string,list<string>> */
    public function errors(): array
    {
        return $this->errors;
    }
}
