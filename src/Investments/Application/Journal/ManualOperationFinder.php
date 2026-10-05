<?php

declare(strict_types=1);

namespace App\Investments\Application\Journal;

use App\Investments\Application\BrokerSync\SyncedAccountGuard;
use App\Investments\Domain\Accounts\AccountRepositoryInterface;
use App\Investments\Domain\Journal\ManualOperation;
use App\Investments\Domain\Journal\ManualOperationRepositoryInterface;
use App\Shared\Domain\User;
use App\Shared\Infrastructure\Symfony\NotFoundException;

/**
 * Finds an operation of the journal of a manual account of the user to change it.
 */
final readonly class ManualOperationFinder
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private ManualOperationRepositoryInterface $operationRepository,
        private SyncedAccountGuard $syncedAccountGuard,
    ) {
    }

    public function get(int $accountId, int $operationId, User $user): ManualOperation
    {
        $account = $this->accountRepository->getByIdAndUser($accountId, $user);
        if (! $account) {
            throw new NotFoundException(sprintf('Account with id "%s" not found', $accountId));
        }
        $this->syncedAccountGuard->assertManual($account);

        $operation = $account->getJournalStartedAt() !== null ? $this->operationRepository->findByIdAndAccount($operationId, $account) : null;

        return $operation ?? throw new NotFoundException(sprintf('Operation with id "%s" not found', $operationId));
    }
}
