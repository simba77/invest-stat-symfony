<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\DTO\BrokerSync;

/**
 * @psalm-api
 */
final readonly class BrokerSyncPageDTO
{
    /**
     * @param list<BrokerSyncOptionDTO> $providers
     * @param list<BrokerSyncOptionDTO> $feeAllocations
     */
    public function __construct(
        public ?BrokerSyncSettingsDTO $settings,
        public array $providers,
        public array $feeAllocations,
    ) {
    }
}
