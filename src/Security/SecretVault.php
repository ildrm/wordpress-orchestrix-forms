<?php

declare(strict_types=1);

namespace Orchestrix\Forms\Security;

use RuntimeException;

final class SecretVault
{
    public function encrypt(string $plaintext): string
    {
        if (! function_exists('sodium_crypto_secretbox')) {
            throw new RuntimeException('Libsodium is required for encrypted credentials.');
        }
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        return base64_encode($nonce . sodium_crypto_secretbox($plaintext, $nonce, $this->key()));
    }

    public function decrypt(string $encoded): string
    {
        $payload = base64_decode($encoded, true);
        if (! is_string($payload) || strlen($payload) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            throw new RuntimeException('Invalid encrypted credential.');
        }
        $nonce = substr($payload, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $plaintext = sodium_crypto_secretbox_open(substr($payload, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), $nonce, $this->key());
        if ($plaintext === false) {
            throw new RuntimeException('Credential decryption failed.');
        }
        return $plaintext;
    }

    private function key(): string
    {
        return sodium_crypto_generichash(wp_salt('secure_auth'), '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
    }
}
