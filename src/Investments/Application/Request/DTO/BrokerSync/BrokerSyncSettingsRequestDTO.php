<?php

declare(strict_types=1);

namespace App\Investments\Application\Request\DTO\BrokerSync;

use App\Investments\Domain\BrokerSync\BrokerProvider;
use App\Investments\Domain\BrokerSync\FeeAllocation;
use Symfony\Component\Validator\Constraints as Assert;

final class BrokerSyncSettingsRequestDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Choice(callback: [BrokerSyncOptions::class, 'providers'])]
        public string $provider = '',
        /** A new token; empty keeps the stored one. */
        #[Assert\Length(max: 1000)]
        public ?string $token = null,
        #[Assert\NotBlank]
        #[Assert\Length(max: 64)]
        public string $externalAccountId = '',
        #[Assert\NotBlank]
        #[Assert\Choice(callback: [BrokerSyncOptions::class, 'feeAllocations'])]
        public string $feeAllocation = '',
        public bool $enabled = true,
    ) {
    }

    public function provider(): BrokerProvider
    {
        return BrokerProvider::from($this->provider);
    }

    public function feeAllocation(): FeeAllocation
    {
        return FeeAllocation::from($this->feeAllocation);
    }

    public function newToken(): ?string
    {
        $token = trim($this->token ?? '');

        return $token !== '' ? $token : null;
    }
}
