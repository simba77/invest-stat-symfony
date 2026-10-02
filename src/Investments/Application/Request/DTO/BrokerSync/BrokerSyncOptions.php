<?php

declare(strict_types=1);

namespace App\Investments\Application\Request\DTO\BrokerSync;

use App\Investments\Domain\BrokerSync\BrokerProvider;
use App\Investments\Domain\BrokerSync\FeeAllocation;

/**
 * Allowed values for the choice constraints of the broker sync requests.
 */
final class BrokerSyncOptions
{
    /**
     * @return list<string>
     */
    public static function providers(): array
    {
        return array_map(static fn (BrokerProvider $provider): string => $provider->value, BrokerProvider::cases());
    }

    /**
     * @return list<string>
     */
    public static function feeAllocations(): array
    {
        return array_map(static fn (FeeAllocation $allocation): string => $allocation->value, FeeAllocation::cases());
    }
}
