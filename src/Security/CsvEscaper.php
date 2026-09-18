<?php

declare(strict_types=1);

namespace Orchestrix\Forms\Security;

final class CsvEscaper
{
    public function cell(mixed $value): string
    {
        $text = is_scalar($value) ? (string) $value : '';
        if (preg_match('/^[\s]*[=+\-@]/u', $text)) {
            return "'" . $text;
        }
        return $text;
    }
}
