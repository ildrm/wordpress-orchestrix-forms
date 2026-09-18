<?php

declare(strict_types=1);

namespace Orchestrix\Forms\Core;

use RuntimeException;

class DomainException extends RuntimeException
{
    public function __construct(string $publicMessage, private readonly string $errorCode, int $status = 400)
    {
        parent::__construct($publicMessage, $status);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }
}
