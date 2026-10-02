<?php

declare(strict_types=1);

namespace App\Investments\Domain\BrokerSync;

interface TokenCipherInterface
{
    public function encrypt(string $token): string;

    public function decrypt(string $encryptedToken): string;
}
