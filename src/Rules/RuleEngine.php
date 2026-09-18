<?php

declare(strict_types=1);

namespace Orchestrix\Forms\Rules;

use InvalidArgumentException;

final class RuleEngine
{
    /**
     * @param array<string,mixed> $node
     * @param array<string,mixed> $context
     */
    public function evaluate(array $node, array $context): bool
    {
        if (isset($node['group'])) {
            $operator = strtoupper((string) $node['group']);
            $rules = is_array($node['rules'] ?? null) ? $node['rules'] : [];
            if ($operator === 'NOT') {
                return ! $this->evaluate((array) ($rules[0] ?? []), $context);
            }
            if (! in_array($operator, ['AND', 'OR'], true)) {
                throw new InvalidArgumentException('Unsupported rule group operator.');
            }
            foreach ($rules as $rule) {
                $matched = $this->evaluate((array) $rule, $context);
                if ($operator === 'AND' && ! $matched) {
                    return false;
                }
                if ($operator === 'OR' && $matched) {
                    return true;
                }
            }
            return $operator === 'AND';
        }

        $left = $this->resolve((string) ($node['field'] ?? ''), $context);
        $right = $node['value'] ?? null;
        $operator = strtolower((string) ($node['operator'] ?? 'equals'));
        return $this->compare($left, $right, $operator);
    }

    private function compare(mixed $left, mixed $right, string $operator): bool
    {
        return match ($operator) {
            'equals' => $left == $right,
            'not_equals' => $left != $right,
            'contains' => is_array($left) ? in_array($right, $left, true) : str_contains((string) $left, (string) $right),
            'not_contains' => ! $this->compare($left, $right, 'contains'),
            'starts_with' => str_starts_with((string) $left, (string) $right),
            'ends_with' => str_ends_with((string) $left, (string) $right),
            'empty' => $left === null || $left === '' || $left === [],
            'not_empty' => ! $this->compare($left, null, 'empty'),
            'greater' => $this->ordered($left, $right) > 0,
            'greater_equal' => $this->ordered($left, $right) >= 0,
            'less' => $this->ordered($left, $right) < 0,
            'less_equal' => $this->ordered($left, $right) <= 0,
            'between' => is_array($right) && count($right) === 2
                && $this->ordered($left, $right[0]) >= 0 && $this->ordered($left, $right[1]) <= 0,
            'in' => is_array($right) && in_array($left, $right, true),
            'not_in' => is_array($right) && ! in_array($left, $right, true),
            'regex' => $this->safeRegex((string) $right, (string) $left),
            'date_before' => $this->dateCompare($left, $right) < 0,
            'date_after' => $this->dateCompare($left, $right) > 0,
            default => throw new InvalidArgumentException('Unsupported rule operator.'),
        };
    }

    private function ordered(mixed $left, mixed $right): int
    {
        if (is_numeric($left) && is_numeric($right)) {
            return (float) $left <=> (float) $right;
        }
        return strcmp((string) $left, (string) $right);
    }

    private function dateCompare(mixed $left, mixed $right): int
    {
        $leftTime = strtotime((string) $left);
        $rightTime = strtotime((string) $right);
        if ($leftTime === false || $rightTime === false) {
            return 0;
        }
        return $leftTime <=> $rightTime;
    }

    private function safeRegex(string $pattern, string $subject): bool
    {
        $nestedQuantifier = '/\((?:[^()\\\\]|\\\\.)*(?:[+*{]|\|)(?:[^()\\\\]|\\\\.)*\)\s*(?:[+*]|\{\d)/';
        if (
            strlen($pattern) > 200
            || strlen($subject) > 10000
            || preg_match('/\(\?[:=!<]|\\[1-9]|\{\d{4,}/', $pattern)
            || preg_match($nestedQuantifier, $pattern)
        ) {
            return false;
        }
        return @preg_match('~' . str_replace('~', '\\~', $pattern) . '~uD', $subject) === 1;
    }

    /** @param array<string,mixed> $context */
    private function resolve(string $path, array $context): mixed
    {
        $value = $context;
        foreach (explode('.', $path) as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }
        return $value;
    }
}
