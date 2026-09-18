<?php

declare(strict_types=1);

namespace Orchestrix\Forms\Payments;

interface GatewayInterface
{
    public function id(): string;

    /**
     * @param array<string,mixed> $payment
     * @return array<string,mixed>
     */
    public function createIntent(array $payment, string $idempotencyKey): array;

    /** @return array<string,mixed> */
    public function refund(string $reference, int $amountMinor, string $idempotencyKey): array;

    /**
     * @param array<string,string> $headers
     * @return array<string,mixed>
     */
    public function parseWebhook(string $payload, array $headers): array;
}
