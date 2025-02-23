<?php

declare(strict_types=1);

namespace App\Investments\Application\Operations\Deals;

use App\Investments\Application\Accounts\AccountBalanceCalculator;
use App\Investments\Domain\Operations\DealRepositoryInterface;
use App\Investments\Domain\Operations\Deals\DealType;
use App\Investments\Infrastructure\Persistence\Repository\BondRepository;
use App\Investments\Infrastructure\Persistence\Repository\FutureRepository;
use App\Investments\Infrastructure\Persistence\Repository\ShareRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateDealCommandHandler
{
    public function __construct(
        private readonly DealRepositoryInterface $dealRepository,
        private readonly AccountBalanceCalculator $accountBalanceCalculatorCalculator,
        public readonly ShareRepository $shareRepository,
        public readonly BondRepository $bondRepository,
        public readonly FutureRepository $futuresRepository,
    ) {
    }

    public function __invoke(UpdateDealCommand $command): void
    {
        $share = $this->shareRepository->findOneBy(['ticker' => $command->ticker, 'stockMarket' => $command->stockMarket]);
        $bond = $this->bondRepository->findOneBy(['ticker' => $command->ticker, 'stockMarket' => $command->stockMarket]);
        $future = $this->futuresRepository->findOneBy(['ticker' => $command->ticker, 'stockMarket' => $command->stockMarket]);

        $deal = $this->dealRepository->findById($command->id);

        $deal->setTicker($command->ticker);
        $deal->setStockMarket($command->stockMarket);
        $deal->setType($command->isShort ? DealType::Short : DealType::Long);
        $deal->setQuantity($command->quantity);
        $deal->setBuyPrice($command->buyPrice);
        $deal->setTargetPrice($command->targetPrice);
        $deal->setShare($share);
        $deal->setBond($bond);
        $deal->setFuture($future);

        $this->dealRepository->save($deal);

        $this->accountBalanceCalculatorCalculator->recalculateBalance($deal->getAccount());
    }
}
