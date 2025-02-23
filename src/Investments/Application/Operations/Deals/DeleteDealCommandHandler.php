<?php

declare(strict_types=1);

namespace App\Investments\Application\Operations\Deals;

use App\Investments\Application\Accounts\AccountBalanceCalculator;
use App\Investments\Domain\Operations\DealRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DeleteDealCommandHandler
{
    public function __construct(
        private readonly DealRepositoryInterface $dealRepository,
        private readonly AccountBalanceCalculator $accountBalanceCalculatorCalculator,
    ) {
    }

    public function __invoke(DeleteDealCommand $command): void
    {
        $deal = $this->dealRepository->findById($command->id);
        $account = $deal->getAccount();
        $this->dealRepository->remove($deal);
        $this->accountBalanceCalculatorCalculator->recalculateBalance($account);
    }
}
