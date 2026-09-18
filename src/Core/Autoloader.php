<?php

declare(strict_types=1);

namespace Orchestrix\Forms\Core;

final class Autoloader
{
    public static function register(string $sourceDirectory): void
    {
        spl_autoload_register(static function (string $class) use ($sourceDirectory): void {
            $prefix = 'Orchestrix\\Forms\\';
            if (! str_starts_with($class, $prefix)) {
                return;
            }

            $relative = substr($class, strlen($prefix));
            $file = rtrim($sourceDirectory, '/\\') . DIRECTORY_SEPARATOR
                . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
            if (is_readable($file)) {
                require $file;
            }
        });
    }
}
