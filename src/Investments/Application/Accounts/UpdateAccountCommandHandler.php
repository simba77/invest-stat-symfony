<?php

declare(strict_types=1);

namespace App\Investments\Application\Accounts;

use App\Investments\Application\BrokerSync\SyncedAccountGuard;
use App\Investments\Domain\Accounts\AccountRepositoryInterface;

use App\Shared\Infrastructure\Symfony\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateAccountCommandHandler
{
    public function __construct(
        public readonly AccountRepositoryInterface $accountRepository,
        private readonly SyncedAccountGuard $syncedAccountGuard,
    ) {
    }

    public function __invoke(UpdateAccountCommand $command): void
    {
        $account = $this->accountRepository->getByIdAndUser($command->accountId, $command->user);
        if (! $account) {
            throw new NotFoundException(sprintf('Account with id "%s" not found', $command->accountId));
        }

        $account->setName($command->name);
        // The cash of a synced account comes from the broker
        if (! $this->syncedAccountGuard->isSynced($account)) {
            $account->setBalance($command->balance);
            $account->setUsdBalance($command->usdBalance);
        }
        $account->setCommission($command->commission);
        $account->setFuturesCommission($command->futuresCommission);
        $account->setSort($command->sort);
        $this->accountRepository->save($account);
    }
}
