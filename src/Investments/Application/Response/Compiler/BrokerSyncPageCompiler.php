<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\Compiler;

use App\Investments\Application\Response\DTO\BrokerSync\BrokerSyncOptionDTO;
use App\Investments\Application\Response\DTO\BrokerSync\BrokerSyncPageDTO;
use App\Investments\Application\Response\DTO\BrokerSync\BrokerSyncSettingsDTO;
use App\Investments\Domain\BrokerSync\BrokerAccountLink;
use App\Investments\Domain\BrokerSync\BrokerProvider;
use App\Investments\Domain\BrokerSync\FeeAllocation;
use App\Shared\Infrastructure\Compiler\CompilerInterface;

/**
 * @template-implements CompilerInterface<BrokerAccountLink|null, BrokerSyncPageDTO>
 */
final class BrokerSyncPageCompiler implements CompilerInterface
{
    /**
     * @param BrokerAccountLink|null $entry
     */
    #[\Override]
    public function compile(mixed $entry): BrokerSyncPageDTO
    {
        return new BrokerSyncPageDTO(
            settings:       $entry !== null ? $this->settings($entry) : null,
            providers:      array_map(
                static fn (BrokerProvider $provider) => new BrokerSyncOptionDTO($provider->value, $provider->title()),
                BrokerProvider::cases(),
            ),
            feeAllocations: array_map(
                static fn (FeeAllocation $allocation) => new BrokerSyncOptionDTO($allocation->value, $allocation->title()),
                FeeAllocation::cases(),
            ),
        );
    }

    private function settings(BrokerAccountLink $link): BrokerSyncSettingsDTO
    {
        return new BrokerSyncSettingsDTO(
            provider:            $link->getProvider()->value,
            externalAccountId:   $link->getExternalAccountId(),
            externalAccountName: $link->getExternalAccountName(),
            feeAllocation:       $link->getFeeAllocation()->value,
            enabled:             $link->isEnabled(),
            lastSyncedAt:        $link->getLastSyncedAt()?->format(\DateTimeInterface::ATOM),
            lastSyncStatus:      $link->getLastSyncStatus()?->value,
            lastSyncMessage:     $link->getLastSyncMessage(),
            discrepancies:       $link->getLastSyncDiscrepancies(),
        );
    }
}
