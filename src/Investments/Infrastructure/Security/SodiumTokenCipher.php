<?php

declare(strict_types=1);

namespace App\Investments\Infrastructure\Security;

use App\Investments\Domain\BrokerSync\TokenCipherInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Encrypts broker API tokens with a secret key, so that a database dump does not expose them.
 *
 * The key is 32 random bytes encoded with base64: `php -r 'echo base64_encode(random_bytes(32)), "\n";'`.
 */
final readonly class SodiumTokenCipher implements TokenCipherInterface
{
    public function __construct(
        #[Autowire('%env(BROKER_TOKEN_KEY)%')]
        private string $key,
    ) {
    }

    #[\Override]
    public function encrypt(string $token): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        return base64_encode($nonce . sodium_crypto_secretbox($token, $nonce, $this->binaryKey()));
    }

    #[\Override]
    public function decrypt(string $encryptedToken): string
    {
        $decoded = base64_decode($encryptedToken, true);
        if ($decoded === false || strlen($decoded) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            throw new \RuntimeException('The broker token is corrupted');
        }

        $nonce = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipherText = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $token = sodium_crypto_secretbox_open($cipherText, $nonce, $this->binaryKey());
        if ($token === false) {
            throw new \RuntimeException('The broker token cannot be decrypted with the current BROKER_TOKEN_KEY');
        }

        return $token;
    }

    private function binaryKey(): string
    {
        $key = base64_decode($this->key, true);
        if ($key === false || strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new \RuntimeException('BROKER_TOKEN_KEY must be 32 random bytes encoded with base64');
        }

        return $key;
    }
}
