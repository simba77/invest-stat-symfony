<?php

declare(strict_types=1);

namespace App\Investments\Application\Accounts;

use App\Investments\Application\Journal\ManualJournal;
use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Accounts\AccountRepositoryInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CreateAccountCommandHandler
{
    public function __construct(
        public readonly AccountRepositoryInterface $accountRepository,
        private readonly ManualJournal $journal,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(CreateAccountCommand $command): void
    {
        $account = new Account(
            userId:            (int) $command->user->getId(),
            name:              $command->name,
            commission:        $command->commission,
            futuresCommission: $command->futuresCommission,
            sort:              $command->sort
        );
        $account->startJournal($this->clock->now());
        $this->accountRepository->save($account);

        // The starting cash is the first entry of the journal
        $this->journal->adjustCash($account, 'RUB', $command->balance);
        $this->journal->adjustCash($account, 'USD', $command->usdBalance);
        $this->journal->adjustBlockedCash($account, 'RUB', $command->blockedBalance);
        $this->journal->adjustBlockedCash($account, 'USD', $command->blockedUsdBalance);
    }
}
