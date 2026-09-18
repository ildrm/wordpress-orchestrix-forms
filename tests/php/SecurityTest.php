<?php

declare(strict_types=1);

namespace Orchestrix\Forms\Tests;

use Orchestrix\Forms\Files\FileValidator;
use Orchestrix\Forms\Notifications\MergeTags;
use Orchestrix\Forms\Security\CsvEscaper;
use Orchestrix\Forms\Security\SecretVault;
use Orchestrix\Forms\Security\SubmissionToken;
use Orchestrix\Forms\Security\UrlGuard;
use Orchestrix\Forms\Webhooks\SignatureVerifier;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class SecurityTest extends TestCase
{
    public function testSubmissionAndWebhookTokensRejectTamperingAndReplay(): void
    {
        $tokens = new SubmissionToken();
        $token = $tokens->issue(12, 4, 60);
        self::assertTrue($tokens->verify($token, 12, 4));
        self::assertFalse($tokens->verify($token, 13, 4));
        self::assertFalse($tokens->verify($token . 'x', 12, 4));

        $webhooks = new SignatureVerifier();
        $now = time();
        $signature = $webhooks->sign('{}', 'secret', $now);
        self::assertTrue($webhooks->verify('{}', 'secret', $now, $signature));
        self::assertFalse($webhooks->verify('{"changed":true}', 'secret', $now, $signature));
        $old = $now - 1000;
        self::assertFalse($webhooks->verify('{}', 'secret', $old, $webhooks->sign('{}', 'secret', $old)));
    }

    public function testPrivateAndNonHttpsWebhookTargetsAreRejected(): void
    {
        $guard = new UrlGuard();

        self::assertFalse($guard->isAllowed('https://127.0.0.1/hook'));
        self::assertFalse($guard->isAllowed('https://[::1]/hook'));
        self::assertFalse($guard->isAllowed('http://example.com/hook'));
        self::assertFalse($guard->isAllowed('https://localhost/hook'));
    }

    public function testFileValidatorRejectsExecutableDoubleExtensionAndMimeMismatch(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'orchestrix-');
        self::assertIsString($path);
        file_put_contents($path, "<?php echo 'unsafe';");

        try {
            $errors = (new FileValidator())->validate(
                $path,
                'invoice.php.jpg',
                1024,
                ['jpg'],
                ['image/jpeg']
            );
            self::assertContains('executable_name', $errors);
            self::assertContains('mime_not_allowed', $errors);
        } finally {
            unlink($path);
        }
    }

    public function testMergeTagsAndCsvValuesAreContextEscaped(): void
    {
        $tags = new MergeTags();

        self::assertSame(
            '&lt;img src=x onerror=alert(1)&gt;',
            $tags->render('{value}', ['value' => '<img src=x onerror=alert(1)>'], 'html')
        );
        self::assertSame(
            'Bcc: attacker@example.test',
            $tags->render('{value}', ['value' => "Bcc: attacker@example.test\r\n"], 'header')
        );
        self::assertSame("'=2+2", (new CsvEscaper())->cell('=2+2'));
    }

    public function testSecretVaultRoundTripAndTamperDetection(): void
    {
        if (! function_exists('sodium_crypto_secretbox')) {
            self::markTestSkipped('Libsodium is not installed.');
        }

        $vault = new SecretVault();
        $encrypted = $vault->encrypt('credential-value');
        self::assertNotSame('credential-value', $encrypted);
        self::assertSame('credential-value', $vault->decrypt($encrypted));

        $payload = base64_decode($encrypted, true);
        self::assertIsString($payload);
        $payload[strlen($payload) - 1] = chr(ord($payload[strlen($payload) - 1]) ^ 1);

        $this->expectException(RuntimeException::class);
        $vault->decrypt(base64_encode($payload));
    }
}
