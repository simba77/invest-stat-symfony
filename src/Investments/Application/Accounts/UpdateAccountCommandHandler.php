<?php

declare(strict_types=1);

namespace App\Investments\Application\Accounts;

use App\Investments\Domain\Accounts\AccountRepositoryInterface;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateAccountCommandHandler
{
    public function __construct(public readonly AccountRepositoryInterface $accountRepository)
    {
    }

    public function __invoke(UpdateAccountCommand $command): void
    {
        $account = $command->account;
        $account->setName($command->name);
        $account->setBalance($command->balance);
        $account->setUsdBalance($command->usdBalance);
        $account->setCommission($command->commission);
        $account->setFuturesCommission($command->futuresCommission);
        $account->setSort($command->sort);
        $this->accountRepository->save($account);
    }
}
