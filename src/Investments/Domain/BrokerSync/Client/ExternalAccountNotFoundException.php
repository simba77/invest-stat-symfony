<?php

declare(strict_types=1);

namespace App\Investments\Domain\BrokerSync\Client;

final class ExternalAccountNotFoundException extends \RuntimeException
{
    public static function withId(string $externalAccountId): self
    {
        return new self(sprintf('The broker has no account "%s" for this token', $externalAccountId));
    }
}
