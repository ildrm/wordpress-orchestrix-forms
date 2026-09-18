<?php

declare(strict_types=1);

namespace Orchestrix\Forms\Workflows;

use LogicException;

final class WorkflowStateMachine
{
    /** @var array<string,list<string>> */
    private const TRANSITIONS = [
        'pending' => ['running', 'cancelled', 'skipped'],
        'running' => ['succeeded', 'failed', 'retrying', 'cancelled', 'skipped'],
        'retrying' => ['running', 'failed', 'cancelled'],
        'failed' => ['retrying', 'cancelled'],
        'succeeded' => [], 'cancelled' => [], 'skipped' => [],
    ];

    public function transition(string $current, string $next): string
    {
        if (! isset(self::TRANSITIONS[$current]) || ! in_array($next, self::TRANSITIONS[$current], true)) {
            throw new LogicException(sprintf('Invalid workflow transition from %s to %s.', $current, $next));
        }
        return $next;
    }
}
