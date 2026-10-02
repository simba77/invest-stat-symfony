<?php

declare(strict_types=1);

namespace App\Investments\Application\BrokerSync;

final readonly class SyncBrokerAccountCommand
{
    public function __construct(
        public int $accountId,
    ) {
    }
}
