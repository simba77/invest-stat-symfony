<?php

declare(strict_types=1);

namespace App\Investments\Application\BrokerSync;

use App\Investments\Domain\Accounts\AccountRepositoryInterface;
use App\Investments\Domain\BrokerSync\BrokerAccountLinkRepositoryInterface;
use App\Shared\Infrastructure\Symfony\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Unlinks the account: the synced records stay and become editable by hand.
 */
#[AsMessageHandler]
final readonly class DeleteBrokerSyncSettingsCommandHandler
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private BrokerAccountLinkRepositoryInterface $linkRepository,
    ) {
    }

    public function __invoke(DeleteBrokerSyncSettingsCommand $command): void
    {
        $account = $this->accountRepository->getByIdAndUser($command->accountId, $command->user);
        $link = $account !== null ? $this->linkRepository->findByAccount($account) : null;
        if ($link === null) {
            throw new NotFoundException(sprintf('Account with id "%s" is not linked to a broker', $command->accountId));
        }

        $this->linkRepository->remove($link);
    }
}
