<?php

declare(strict_types=1);

namespace App\Investments\Application\Operations\Deals;

use App\Investments\Application\BrokerSync\SyncedAccountGuard;
use App\Investments\Application\Journal\ManualJournal;
use App\Investments\Domain\Instruments\InstrumentRepositoryInterface;
use App\Investments\Domain\Journal\ManualOperationRepositoryInterface;
use App\Investments\Domain\Journal\ManualOperationType;
use App\Investments\Domain\Operations\DealRepositoryInterface;
use App\Investments\Domain\Operations\Deals\DealType;
use App\Shared\Infrastructure\Symfony\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Corrects the purchase that opened the deal. The price, the security and the target are the
 * purchase's, so they change for the parts of it sold already; the quantity changes by the difference.
 */
#[AsMessageHandler]
final readonly class UpdateDealCommandHandler
{
    public function __construct(
        private DealRepositoryInterface $dealRepository,
        private InstrumentRepositoryInterface $instrumentRepository,
        private ManualOperationRepositoryInterface $operationRepository,
        private SyncedAccountGuard $syncedAccountGuard,
        private ManualJournal $journal,
    ) {
    }

    public function __invoke(UpdateDealCommand $command): void
    {
        $deal = $this->dealRepository->findById($command->id) ?? throw new NotFoundException(sprintf('Deal with id "%s" not found', $command->id));
        $account = $deal->getAccount();
        $this->syncedAccountGuard->assertManual($account);

        $lot = $this->journal->lotOf($deal);
        $opening = $this->operationRepository->findOpening($account, $lot)
            ?? throw new NotFoundException(sprintf('The purchase of deal "%s" is not in the journal', $command->id));
        $opening->correctOpening(
            type:        ManualOperationType::opening($command->isShort ? DealType::Short : DealType::Long),
            instrument:  $this->instrumentRepository->findByTickerAndStockMarket($command->ticker, $command->stockMarket),
            ticker:      $command->ticker,
            stockMarket: $command->stockMarket,
            quantity:    $opening->getQuantity() + $command->quantity - $deal->getQuantity(),
            price:       $command->buyPrice,
            targetPrice: $command->targetPrice,
        );
        $this->journal->record($account);
    }
}
