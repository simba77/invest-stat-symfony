<?php

declare(strict_types=1);

namespace App\Tests\Investments\Infrastructure\Security;

use App\Investments\Infrastructure\Security\SodiumTokenCipher;
use PHPUnit\Framework\TestCase;

final class SodiumTokenCipherTest extends TestCase
{
    public function testDecryptsWhatItEncrypted(): void
    {
        $cipher = new SodiumTokenCipher(base64_encode(str_repeat('k', 32)));

        $encrypted = $cipher->encrypt('t.secret-token');

        self::assertStringNotContainsString('secret-token', $encrypted);
        self::assertSame('t.secret-token', $cipher->decrypt($encrypted));
    }

    public function testEncryptsSameTokenDifferently(): void
    {
        $cipher = new SodiumTokenCipher(base64_encode(str_repeat('k', 32)));

        self::assertNotSame($cipher->encrypt('token'), $cipher->encrypt('token'));
    }

    public function testRejectsTokenEncryptedWithAnotherKey(): void
    {
        $encrypted = (new SodiumTokenCipher(base64_encode(str_repeat('a', 32))))->encrypt('token');

        $this->expectExceptionMessage('cannot be decrypted');

        (new SodiumTokenCipher(base64_encode(str_repeat('b', 32))))->decrypt($encrypted);
    }

    /**
     * @dataProvider invalidKeys
     */
    public function testRequiresKeyOf32Bytes(string $key): void
    {
        $this->expectExceptionMessage('BROKER_TOKEN_KEY must be 32 random bytes encoded with base64');

        (new SodiumTokenCipher($key))->encrypt('token');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidKeys(): iterable
    {
        yield 'empty' => [''];
        yield 'short' => [base64_encode('short')];
        yield 'not base64' => ['not base64!'];
    }
}
