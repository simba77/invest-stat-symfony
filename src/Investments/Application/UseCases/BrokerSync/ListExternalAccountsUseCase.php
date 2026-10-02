<?php

declare(strict_types=1);

namespace App\Investments\Application\UseCases\BrokerSync;

use App\Investments\Application\Response\DTO\BrokerSync\ExternalAccountDTO;
use App\Investments\Domain\BrokerSync\BrokerAccountLink;
use App\Investments\Domain\BrokerSync\BrokerProvider;
use App\Investments\Domain\BrokerSync\Client\BrokerClientFactoryInterface;
use App\Investments\Domain\BrokerSync\TokenCipherInterface;

/**
 * Lists the broker accounts a token can read, to pick the one to link.
 */
final readonly class ListExternalAccountsUseCase
{
    public function __construct(
        private BrokerClientFactoryInterface $clientFactory,
        private TokenCipherInterface $tokenCipher,
    ) {
    }

    /**
     * @param string|null $token a new token, or null to use the one stored in the link
     * @return list<ExternalAccountDTO>
     */
    public function execute(BrokerProvider $provider, ?string $token, ?BrokerAccountLink $link): array
    {
        if ($token === null && $link !== null && $link->getProvider() === $provider) {
            $token = $this->tokenCipher->decrypt($link->getEncryptedToken());
        }
        if ($token === null) {
            throw new \LogicException('A token is required to list broker accounts');
        }

        $result = [];
        foreach ($this->clientFactory->forProvider($provider)->getAccounts($token) as $account) {
            $result[] = new ExternalAccountDTO($account->id, $account->name, $account->readOnly);
        }

        return $result;
    }
}
