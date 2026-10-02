<?php

declare(strict_types=1);

namespace App\Investments\Application\BrokerSync;

use App\Investments\Domain\Accounts\AccountRepositoryInterface;
use App\Investments\Domain\BrokerSync\BrokerAccountLink;
use App\Investments\Domain\BrokerSync\BrokerAccountLinkRepositoryInterface;
use App\Investments\Domain\BrokerSync\Client\BrokerClientFactoryInterface;
use App\Investments\Domain\BrokerSync\Client\ExternalAccount;
use App\Investments\Domain\BrokerSync\Client\ExternalAccountNotFoundException;
use App\Investments\Domain\BrokerSync\TokenCipherInterface;
use App\Shared\Infrastructure\Symfony\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Links an account to a broker account after checking that the token can read it.
 */
#[AsMessageHandler]
final readonly class SaveBrokerSyncSettingsCommandHandler
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private BrokerAccountLinkRepositoryInterface $linkRepository,
        private BrokerClientFactoryInterface $clientFactory,
        private TokenCipherInterface $tokenCipher,
    ) {
    }

    public function __invoke(SaveBrokerSyncSettingsCommand $command): void
    {
        $account = $this->accountRepository->getByIdAndUser($command->accountId, $command->user);
        if ($account === null) {
            throw new NotFoundException(sprintf('Account with id "%s" not found', $command->accountId));
        }

        $link = $this->linkRepository->findByAccount($account);
        $token = $command->token ?? ($link !== null ? $this->tokenCipher->decrypt($link->getEncryptedToken()) : null);
        if ($token === null) {
            throw new \LogicException('A token is required to link an account');
        }

        $externalAccount = $this->findExternalAccount($command, $token);

        if ($link === null) {
            $link = new BrokerAccountLink(
                account:                 $account,
                provider:                $command->provider,
                encryptedToken:          $this->tokenCipher->encrypt($token),
                externalAccountId:       $externalAccount->id,
                externalAccountName:     $externalAccount->name,
                externalAccountOpenedAt: $externalAccount->openedAt,
                feeAllocation:           $command->feeAllocation,
                enabled:                 $command->enabled,
            );
        } else {
            if ($command->token !== null) {
                $link->replaceToken($this->tokenCipher->encrypt($command->token));
            }
            $link->reconfigure(
                provider:                $command->provider,
                externalAccountId:       $externalAccount->id,
                externalAccountName:     $externalAccount->name,
                externalAccountOpenedAt: $externalAccount->openedAt,
                feeAllocation:           $command->feeAllocation,
                enabled:                 $command->enabled,
            );
        }

        $this->linkRepository->save($link);
    }

    private function findExternalAccount(SaveBrokerSyncSettingsCommand $command, string $token): ExternalAccount
    {
        foreach ($this->clientFactory->forProvider($command->provider)->getAccounts($token) as $externalAccount) {
            if ($externalAccount->id === $command->externalAccountId) {
                return $externalAccount;
            }
        }

        throw ExternalAccountNotFoundException::withId($command->externalAccountId);
    }
}
