<?php

declare(strict_types=1);

namespace App\Deposits\Application;

use App\Deposits\Domain\Deposit;
use App\Deposits\Domain\DepositRepositoryInterface;
use Carbon\Carbon;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CreateDepositCommandHandler
{
    public function __construct(
        private readonly DepositRepositoryInterface $depositRepository
    ) {
    }

    public function __invoke(CreateDepositCommand $createDeposit): void
    {
        $deposit = new Deposit(
            sum:            $createDeposit->amount,
            type:           $createDeposit->type,
            user:           $createDeposit->user,
            depositAccount: $createDeposit->account,
            date:           Carbon::parse($createDeposit->date)
        );
        $this->depositRepository->save($deposit);
    }
}
