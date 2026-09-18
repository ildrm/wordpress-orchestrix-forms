<?php

declare(strict_types=1);

namespace Orchestrix\Forms\Payments;

use LogicException;

final class PaymentStateMachine
{
    /** @var array<string,list<string>> */
    private const TRANSITIONS = [
        'pending' => ['requires_action', 'authorized', 'processing', 'succeeded', 'failed', 'cancelled'],
        'requires_action' => ['authorized', 'processing', 'succeeded', 'failed', 'cancelled'],
        'authorized' => ['processing', 'succeeded', 'cancelled'],
        'processing' => ['succeeded', 'failed', 'cancelled'],
        'succeeded' => ['partially_refunded', 'refunded', 'disputed'],
        'partially_refunded' => ['partially_refunded', 'refunded', 'disputed'],
        'refunded' => [], 'failed' => [], 'cancelled' => [], 'disputed' => ['partially_refunded', 'refunded'],
    ];

    public function transition(string $current, string $next): string
    {
        if (! isset(self::TRANSITIONS[$current]) || ! in_array($next, self::TRANSITIONS[$current], true)) {
            throw new LogicException(sprintf('Invalid payment transition from %s to %s.', $current, $next));
        }
        return $next;
    }

    public function refundState(int $amountMinor, int $refundedMinor, int $newRefundMinor): string
    {
        if ($newRefundMinor <= 0 || $refundedMinor + $newRefundMinor > $amountMinor) {
            throw new LogicException('Invalid refund amount.');
        }
        return $refundedMinor + $newRefundMinor === $amountMinor ? 'refunded' : 'partially_refunded';
    }
}
