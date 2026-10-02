<?php

declare(strict_types=1);

namespace App\Investments\Domain\BrokerSync;

final class BrokerSyncDisabledException extends \RuntimeException
{
    public static function forAccount(int $accountId): self
    {
        return new self(sprintf('Sync is turned off for account "%s"', $accountId));
    }
}
