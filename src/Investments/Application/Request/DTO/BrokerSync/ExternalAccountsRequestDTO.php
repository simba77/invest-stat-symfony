<?php

declare(strict_types=1);

namespace App\Investments\Application\Request\DTO\BrokerSync;

use App\Investments\Domain\BrokerSync\BrokerProvider;
use Symfony\Component\Validator\Constraints as Assert;

final class ExternalAccountsRequestDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Choice(callback: [BrokerSyncOptions::class, 'providers'])]
        public string $provider = '',
        /** Empty uses the token stored for the account. */
        #[Assert\Length(max: 1000)]
        public ?string $token = null,
    ) {
    }

    public function provider(): BrokerProvider
    {
        return BrokerProvider::from($this->provider);
    }

    public function newToken(): ?string
    {
        $token = trim($this->token ?? '');

        return $token !== '' ? $token : null;
    }
}
