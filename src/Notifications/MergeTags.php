<?php

declare(strict_types=1);

namespace Orchestrix\Forms\Notifications;

final class MergeTags
{
    /** @param array<string,mixed> $values */
    public function render(string $template, array $values, string $context = 'text'): string
    {
        return preg_replace_callback('/\{([a-zA-Z0-9_.-]+)\}/', function (array $match) use ($values, $context): string {
            $value = $this->resolve($match[1], $values);
            $text = is_scalar($value) ? (string) $value : (wp_json_encode($value) ?: '');
            return match ($context) {
                'html' => esc_html($text), 'url' => rawurlencode($text),
                'json' => trim(wp_json_encode($text) ?: '""', '"'),
                'header' => str_replace(["\r", "\n"], '', $text),
                default => wp_strip_all_tags($text),
            };
        }, $template) ?? $template;
    }

    /** @param array<string,mixed> $values */
    private function resolve(string $path, array $values): mixed
    {
        $value = $values;
        foreach (explode('.', $path) as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return '';
            }
            $value = $value[$segment];
        }
        return $value;
    }
}
