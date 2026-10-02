<?php

declare(strict_types=1);

namespace App\Investments\Application\BrokerSync;

use App\Shared\Domain\User;

final readonly class DeleteBrokerSyncSettingsCommand
{
    public function __construct(
        public int $accountId,
        public User $user,
    ) {
    }
}
