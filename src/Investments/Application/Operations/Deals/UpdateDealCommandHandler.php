<?php

declare(strict_types=1);

namespace App\Investments\Application\Operations\Deals;

use App\Investments\Application\Accounts\AccountBalanceCalculator;
use App\Investments\Application\BrokerSync\SyncedAccountGuard;
use App\Investments\Domain\Instruments\InstrumentRepositoryInterface;
use App\Investments\Domain\Operations\DealRepositoryInterface;
use App\Investments\Domain\Operations\Deals\DealType;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateDealCommandHandler
{
    public function __construct(
        private readonly DealRepositoryInterface $dealRepository,
        private readonly AccountBalanceCalculator $accountBalanceCalculatorCalculator,
        private readonly InstrumentRepositoryInterface $instrumentRepository,
        private readonly SyncedAccountGuard $syncedAccountGuard,
    ) {
    }

    public function __invoke(UpdateDealCommand $command): void
    {
        $deal = $this->dealRepository->findById($command->id);
        $this->syncedAccountGuard->assertManual($deal->getAccount());

        $deal->setTicker($command->ticker);
        $deal->setStockMarket($command->stockMarket);
        $deal->setType($command->isShort ? DealType::Short : DealType::Long);
        $deal->setQuantity($command->quantity);
        $deal->setBuyPrice($command->buyPrice);
        $deal->setTargetPrice($command->targetPrice);
        $deal->setInstrument($this->instrumentRepository->findByTickerAndStockMarket($command->ticker, $command->stockMarket));

        $this->dealRepository->save($deal);

        $this->accountBalanceCalculatorCalculator->recalculateBalance($deal->getAccount());
    }
}
