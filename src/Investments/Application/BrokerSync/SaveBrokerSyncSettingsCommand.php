<?php

declare(strict_types=1);

namespace App\Investments\Application\BrokerSync;

use App\Investments\Domain\BrokerSync\BrokerProvider;
use App\Investments\Domain\BrokerSync\FeeAllocation;
use App\Shared\Domain\User;

final readonly class SaveBrokerSyncSettingsCommand
{
    /**
     * @param string|null $token a new token, or null to keep the stored one
     */
    public function __construct(
        public int $accountId,
        public User $user,
        public BrokerProvider $provider,
        public ?string $token,
        public string $externalAccountId,
        public FeeAllocation $feeAllocation,
        public bool $enabled,
    ) {
    }
}
