<?php

declare(strict_types=1);

namespace Orchestrix\Forms\Webhooks;

final class SignatureVerifier
{
    public function sign(string $payload, string $secret, int $timestamp): string
    {
        return hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
    }

    public function verify(string $payload, string $secret, int $timestamp, string $signature, int $tolerance = 300): bool
    {
        if ($timestamp < time() - $tolerance || $timestamp > time() + $tolerance) {
            return false;
        }
        return hash_equals($this->sign($payload, $secret, $timestamp), $signature);
    }
}
