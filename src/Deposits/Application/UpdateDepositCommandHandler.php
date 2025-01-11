<?php

declare(strict_types=1);

namespace App\Deposits\Application;

use App\Deposits\Domain\DepositRepositoryInterface;
use Carbon\Carbon;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateDepositCommandHandler
{
    public function __construct(
        private readonly DepositRepositoryInterface $depositRepository
    ) {
    }

    public function __invoke(UpdateDepositCommand $updateDeposit): void
    {
        $deposit = $updateDeposit->deposit;
        $deposit->setSum($updateDeposit->amount);
        $deposit->setDepositAccount($updateDeposit->account);
        $deposit->setType($updateDeposit->type);
        $deposit->setDate(Carbon::parse($updateDeposit->date));

        $this->depositRepository->save($deposit);
    }
}
