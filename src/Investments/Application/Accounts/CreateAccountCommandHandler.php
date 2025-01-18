<?php

declare(strict_types=1);

namespace App\Investments\Application\Accounts;

use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Accounts\AccountRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CreateAccountCommandHandler
{
    public function __construct(
        public readonly AccountRepositoryInterface $accountRepository,
    ) {
    }

    public function __invoke(CreateAccountCommand $command): void
    {
        $account = new Account(
            userId:            $command->user->getId(),
            name:              $command->name,
            balance:           $command->balance,
            usdBalance:        $command->usdBalance,
            commission:        $command->commission,
            futuresCommission: $command->futuresCommission,
            sort:              $command->sort
        );

        $this->accountRepository->save($account);
    }
}
