<?php

declare(strict_types=1);

namespace Orchestrix\Forms\Security;

final class SubmissionToken
{
    public function issue(int $formId, int $versionId, int $ttl = 7200): string
    {
        $payload = $formId . ':' . $versionId . ':' . (time() + $ttl);
        return base64_encode($payload . ':' . hash_hmac('sha256', $payload, wp_salt('nonce')));
    }

    public function verify(string $token, int $formId, int $versionId): bool
    {
        $decoded = base64_decode($token, true);
        if (! is_string($decoded)) {
            return false;
        }
        $parts = explode(':', $decoded);
        if (count($parts) !== 4) {
            return false;
        }
        [$tokenForm, $tokenVersion, $expiry, $signature] = $parts;
        $payload = $tokenForm . ':' . $tokenVersion . ':' . $expiry;
        return (int) $tokenForm === $formId && (int) $tokenVersion === $versionId
            && (int) $expiry >= time() && hash_equals(hash_hmac('sha256', $payload, wp_salt('nonce')), $signature);
    }
}
