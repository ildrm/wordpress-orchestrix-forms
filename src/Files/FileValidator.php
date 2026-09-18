<?php

declare(strict_types=1);

namespace Orchestrix\Forms\Files;

final class FileValidator
{
    /**
     * @param list<string> $extensions
     * @param list<string> $mimeTypes
     * @return list<string>
     */
    public function validate(string $path, string $originalName, int $maxBytes, array $extensions, array $mimeTypes): array
    {
        $errors = [];
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension === '' || ! in_array($extension, array_map('strtolower', $extensions), true)) {
            $errors[] = 'extension_not_allowed';
        }
        if (preg_match('/\.(?:php\d*|phtml|phar|cgi|pl|py|sh|exe|js)(?:\.|$)/i', $originalName)) {
            $errors[] = 'executable_name';
        }
        if (! is_file($path) || filesize($path) === false || filesize($path) > $maxBytes) {
            $errors[] = 'invalid_size';
        } elseif (class_exists('finfo')) {
            $detected = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
            if (! is_string($detected) || ! in_array(strtolower($detected), array_map('strtolower', $mimeTypes), true)) {
                $errors[] = 'mime_not_allowed';
            }
        }
        return array_values(array_unique($errors));
    }
}
