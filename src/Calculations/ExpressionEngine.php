<?php

declare(strict_types=1);

namespace Orchestrix\Forms\Calculations;

final class ExpressionEngine
{
    /** @var list<array{type:string,value:string}> */
    private array $tokens = [];
    private int $position = 0;
    /** @var array<string,mixed> */
    private array $context = [];

    /** @param array<string,mixed> $context */
    public function evaluate(string $expression, array $context): mixed
    {
        if (strlen($expression) > 4096) {
            throw new ExpressionException('Expression is too long.');
        }
        $this->tokens = $this->tokenize($expression);
        $this->position = 0;
        $this->context = $context;
        $result = $this->parseOr();
        if ($this->current()['type'] !== 'eof') {
            throw new ExpressionException('Unexpected token: ' . $this->current()['value']);
        }
        return $result;
    }

    /** @return list<string> */
    public function dependencies(string $expression): array
    {
        $dependencies = [];
        foreach ($this->tokenize($expression) as $index => $token) {
            if ($token['type'] === 'identifier' && (($this->tokenize($expression)[$index + 1]['value'] ?? '') !== '(')) {
                $dependencies[] = explode('.', $token['value'])[0];
            }
        }
        return array_values(array_unique($dependencies));
    }

    /**
     * @param array<string,string> $expressions
     * @return list<string>
     */
    public function order(array $expressions): array
    {
        $visiting = [];
        $visited = [];
        $order = [];
        $visit = function (string $name) use (&$visit, &$visiting, &$visited, &$order, $expressions): void {
            if (isset($visiting[$name])) {
                throw new ExpressionException('Circular calculation dependency detected at ' . $name . '.');
            }
            if (isset($visited[$name])) {
                return;
            }
            $visiting[$name] = true;
            foreach ($this->dependencies($expressions[$name]) as $dependency) {
                if (isset($expressions[$dependency])) {
                    $visit($dependency);
                }
            }
            unset($visiting[$name]);
            $visited[$name] = true;
            $order[] = $name;
        };
        foreach (array_keys($expressions) as $name) {
            $visit($name);
        }
        return $order;
    }

    /** @return list<array{type:string,value:string}> */
    private function tokenize(string $expression): array
    {
        $tokens = [];
        $offset = 0;
        $length = strlen($expression);
        while ($offset < $length) {
            if (preg_match('/\G\s+/A', $expression, $match, 0, $offset)) {
                $offset += strlen($match[0]);
                continue;
            }
            if (preg_match('/\G(?:\d+(?:\.\d+)?|\.\d+)/A', $expression, $match, 0, $offset)) {
                $tokens[] = ['type' => 'number', 'value' => $match[0]];
            } elseif (preg_match('/\G"(?:[^"\\\\]|\\\\.)*"|\G\'(?:[^\'\\\\]|\\\\.)*\'/A', $expression, $match, 0, $offset)) {
                $tokens[] = ['type' => 'string', 'value' => stripcslashes(substr($match[0], 1, -1))];
            } elseif (preg_match('/\G[A-Za-z_][A-Za-z0-9_.]*/A', $expression, $match, 0, $offset)) {
                $tokens[] = ['type' => 'identifier', 'value' => $match[0]];
            } elseif (preg_match('/\G(?:===|!==|==|!=|<=|>=|&&|\|\||[+\-*\/%^<>()!,])/A', $expression, $match, 0, $offset)) {
                $tokens[] = ['type' => 'operator', 'value' => $match[0]];
            } else {
                throw new ExpressionException('Invalid token near position ' . $offset . '.');
            }
            $offset += strlen($match[0]);
            if (count($tokens) > 1024) {
                throw new ExpressionException('Expression has too many tokens.');
            }
        }
        $tokens[] = ['type' => 'eof', 'value' => ''];
        return $tokens;
    }

    private function parseOr(): mixed
    {
        $value = $this->parseAnd();
        while ($this->accept('||')) {
            $value = (bool) $value || (bool) $this->parseAnd();
        }
        return $value;
    }

    private function parseAnd(): mixed
    {
        $value = $this->parseComparison();
        while ($this->accept('&&')) {
            $value = (bool) $value && (bool) $this->parseComparison();
        }
        return $value;
    }

    private function parseComparison(): mixed
    {
        $value = $this->parseAdditive();
        while (in_array($this->current()['value'], ['==', '===', '!=', '!==', '<', '<=', '>', '>='], true)) {
            $operator = $this->consume()['value'];
            $right = $this->parseAdditive();
            $value = match ($operator) {
                '==' => $value == $right, '===' => $value === $right,
                '!=' => $value != $right, '!==' => $value !== $right,
                '<' => $value < $right, '<=' => $value <= $right,
                '>' => $value > $right, '>=' => $value >= $right,
                default => throw new ExpressionException('Unknown comparison operator.'),
            };
        }
        return $value;
    }

    private function parseAdditive(): mixed
    {
        $value = $this->parseMultiplicative();
        while (in_array($this->current()['value'], ['+', '-'], true)) {
            $operator = $this->consume()['value'];
            $right = $this->parseMultiplicative();
            $value = $this->binary($value, $right, $operator);
        }
        return $value;
    }

    private function parseMultiplicative(): mixed
    {
        $value = $this->parsePower();
        while (in_array($this->current()['value'], ['*', '/', '%'], true)) {
            $operator = $this->consume()['value'];
            $right = $this->parsePower();
            $value = $this->binary($value, $right, $operator);
        }
        return $value;
    }

    private function parsePower(): mixed
    {
        $value = $this->parseUnary();
        if ($this->accept('^')) {
            $value = $this->binary($value, $this->parsePower(), '^');
        }
        return $value;
    }

    private function parseUnary(): mixed
    {
        if ($this->accept('!')) {
            return ! (bool) $this->parseUnary();
        }
        if ($this->accept('-')) {
            return -1 * (float) $this->parseUnary();
        }
        return $this->parsePrimary();
    }

    private function parsePrimary(): mixed
    {
        $token = $this->consume();
        if ($token['type'] === 'number') {
            return str_contains($token['value'], '.') ? (float) $token['value'] : (int) $token['value'];
        }
        if ($token['type'] === 'string') {
            return $token['value'];
        }
        if ($token['value'] === '(') {
            $value = $this->parseOr();
            $this->expect(')');
            return $value;
        }
        if ($token['type'] !== 'identifier') {
            throw new ExpressionException('Expected a value.');
        }
        $lower = strtolower($token['value']);
        if (in_array($lower, ['true', 'false', 'null'], true)) {
            return match ($lower) {
                'true' => true, 'false' => false, default => null
            };
        }
        if ($this->accept('(')) {
            $arguments = [];
            if ($this->current()['value'] !== ')') {
                do {
                    $arguments[] = $this->parseOr();
                } while ($this->accept(','));
            }
            $this->expect(')');
            return $this->call($token['value'], $arguments);
        }
        return $this->resolve($token['value']);
    }

    private function binary(mixed $left, mixed $right, string $operator): mixed
    {
        if (is_array($left) || is_array($right)) {
            $lefts = is_array($left) ? array_values($left) : array_fill(0, count((array) $right), $left);
            $rights = is_array($right) ? array_values($right) : array_fill(0, count((array) $left), $right);
            $count = max(count($lefts), count($rights));
            $result = [];
            for ($index = 0; $index < $count; $index++) {
                $result[] = $this->binary($lefts[$index] ?? 0, $rights[$index] ?? 0, $operator);
            }
            return $result;
        }
        $a = (float) $left;
        $b = (float) $right;
        if (in_array($operator, ['/', '%'], true) && $b == 0.0) {
            throw new ExpressionException('Division by zero.');
        }
        return match ($operator) {
            '+' => $a + $b, '-' => $a - $b, '*' => $a * $b,
            '/' => $a / $b, '%' => fmod($a, $b), '^' => $a ** $b,
            default => throw new ExpressionException('Unknown arithmetic operator.'),
        };
    }

    /** @param list<mixed> $arguments */
    private function call(string $function, array $arguments): mixed
    {
        $name = strtoupper($function);
        $flat = [];
        array_walk_recursive($arguments, static function (mixed $value) use (&$flat): void {
            $flat[] = $value;
        });
        return match ($name) {
            'SUM' => array_sum(array_map('floatval', $flat)),
            'AVG' => $flat === [] ? 0.0 : array_sum(array_map('floatval', $flat)) / count($flat),
            'MIN' => $flat === [] ? null : min($flat), 'MAX' => $flat === [] ? null : max($flat),
            'COUNT' => count($flat), 'ROUND' => round((float) ($arguments[0] ?? 0), (int) ($arguments[1] ?? 0)),
            'CEIL' => ceil((float) ($arguments[0] ?? 0)), 'FLOOR' => floor((float) ($arguments[0] ?? 0)),
            'ABS' => abs((float) ($arguments[0] ?? 0)),
            'IF' => (bool) ($arguments[0] ?? false) ? ($arguments[1] ?? null) : ($arguments[2] ?? null),
            'DATEDIFF' => $this->dateDiff($arguments[0] ?? null, $arguments[1] ?? null),
            'DATEADD' => $this->dateAdd($arguments[0] ?? null, (int) ($arguments[1] ?? 0)),
            'CONCAT' => implode('', array_map('strval', $flat)),
            'LOWER' => strtolower((string) ($arguments[0] ?? '')), 'UPPER' => strtoupper((string) ($arguments[0] ?? '')),
            default => throw new ExpressionException('Unknown calculation function: ' . $name),
        };
    }

    private function dateDiff(mixed $end, mixed $start): int
    {
        try {
            return (new \DateTimeImmutable((string) $start))->diff(new \DateTimeImmutable((string) $end))->days ?: 0;
        } catch (\Exception) {
            throw new ExpressionException('Invalid date value.');
        }
    }

    private function dateAdd(mixed $date, int $days): string
    {
        try {
            return (new \DateTimeImmutable((string) $date))->modify(($days >= 0 ? '+' : '') . $days . ' days')->format('Y-m-d');
        } catch (\Exception) {
            throw new ExpressionException('Invalid date value.');
        }
    }

    private function resolve(string $path): mixed
    {
        $segments = explode('.', $path);
        $value = $this->context[array_shift($segments)] ?? null;
        foreach ($segments as $segment) {
            if (is_array($value) && array_is_list($value)) {
                $value = array_map(static fn (mixed $row): mixed => is_array($row) ? ($row[$segment] ?? null) : null, $value);
            } elseif (is_array($value)) {
                $value = $value[$segment] ?? null;
            } else {
                return null;
            }
        }
        return $value;
    }

    /** @return array{type:string,value:string} */
    private function current(): array
    {
        return $this->tokens[$this->position] ?? ['type' => 'eof', 'value' => ''];
    }

    /** @return array{type:string,value:string} */
    private function consume(): array
    {
        return $this->tokens[$this->position++] ?? ['type' => 'eof', 'value' => ''];
    }

    private function accept(string $value): bool
    {
        if ($this->current()['value'] !== $value) {
            return false;
        }
        $this->position++;
        return true;
    }

    private function expect(string $value): void
    {
        if (! $this->accept($value)) {
            throw new ExpressionException('Expected ' . $value . '.');
        }
    }
}
