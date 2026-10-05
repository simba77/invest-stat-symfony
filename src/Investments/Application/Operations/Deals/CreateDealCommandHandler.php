<?php

declare(strict_types=1);

namespace App\Investments\Application\Operations\Deals;

use App\Investments\Application\BrokerSync\SyncedAccountGuard;
use App\Investments\Application\Journal\ManualJournal;
use App\Investments\Domain\Accounts\AccountRepositoryInterface;
use App\Investments\Domain\Instruments\Bond;
use App\Investments\Domain\Instruments\InstrumentRepositoryInterface;
use App\Investments\Domain\Journal\ManualOperation;
use App\Investments\Domain\Journal\ManualOperationType;
use App\Investments\Domain\Operations\Deals\DealType;
use App\Shared\Infrastructure\Symfony\NotFoundException;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Records a purchase or a short sale in the journal; the deal and the cash follow from it.
 */
#[AsMessageHandler]
final readonly class CreateDealCommandHandler
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private InstrumentRepositoryInterface $instrumentRepository,
        private SyncedAccountGuard $syncedAccountGuard,
        private ManualJournal $journal,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(CreateDealCommand $command): void
    {
        $account = $this->accountRepository->findById($command->accountId);
        if (! $account || $account->getUserId() !== $command->userId) {
            throw new NotFoundException('No user or account found');
        }
        $this->syncedAccountGuard->assertManual($account);

        $instrument = $this->instrumentRepository->findByTickerAndStockMarket($command->ticker, $command->stockMarket);
        $this->journal->record($account, ManualOperation::open(
            account:         $account,
            type:            ManualOperationType::opening($command->isShort ? DealType::Short : DealType::Long),
            executedAt:      $this->clock->now(),
            instrument:      $instrument,
            ticker:          $command->ticker,
            stockMarket:     $command->stockMarket,
            quantity:        $command->quantity,
            price:           $command->buyPrice,
            targetPrice:     $command->targetPrice,
            accruedInterest: $instrument instanceof Bond ? $instrument->getCouponAccumulated() : null,
        ));
    }
}
