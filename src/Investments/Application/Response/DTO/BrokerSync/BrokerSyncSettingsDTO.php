<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\DTO\BrokerSync;

/**
 * @psalm-api
 */
final readonly class BrokerSyncSettingsDTO
{
    /**
     * @param list<array{name: string, broker: string, calculated: string}> $discrepancies
     */
    public function __construct(
        public string $provider,
        public string $externalAccountId,
        public string $externalAccountName,
        public string $feeAllocation,
        public bool $enabled,
        public ?string $lastSyncedAt,
        public ?string $lastSyncStatus,
        public ?string $lastSyncMessage,
        public array $discrepancies,
    ) {
    }
}
