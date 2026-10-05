<?php

declare(strict_types=1);

namespace App\Investments\Application\Accounts;

use App\Investments\Application\BrokerSync\SyncedAccountGuard;
use App\Investments\Domain\Accounts\AccountCannotBeClosedException;
use App\Investments\Domain\Accounts\AccountRepositoryInterface;
use App\Shared\Infrastructure\Symfony\NotFoundException;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Closes an account or opens it again; a closed one keeps its records.
 */
#[AsMessageHandler]
final readonly class CloseAccountCommandHandler
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private SyncedAccountGuard $syncedAccountGuard,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(CloseAccountCommand $command): void
    {
        $account = $this->accountRepository->getByIdAndUser($command->accountId, $command->user);
        if (! $account) {
            throw new NotFoundException(sprintf('Account with id "%s" not found', $command->accountId));
        }

        if (! $command->close) {
            $account->reopen();
        } elseif ($this->syncedAccountGuard->isSynced($account)) {
            // The scheduled sync would keep changing it
            throw AccountCannotBeClosedException::synced($account);
        } else {
            $account->close($this->clock->now());
        }
        $this->accountRepository->save($account);
    }
}
