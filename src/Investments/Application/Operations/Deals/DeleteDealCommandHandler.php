<?php

declare(strict_types=1);

namespace App\Investments\Application\Operations\Deals;

use App\Investments\Application\BrokerSync\SyncedAccountGuard;
use App\Investments\Application\Journal\ManualJournal;
use App\Investments\Domain\Journal\DealCannotBeDeletedException;
use App\Investments\Domain\Journal\ManualOperationRepositoryInterface;
use App\Investments\Domain\Operations\DealRepositoryInterface;
use App\Investments\Domain\Operations\Deals\DealStatus;
use App\Shared\Infrastructure\Symfony\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Takes the deal out of the purchase that opened it: the whole purchase goes when nothing else
 * is left of it, with its sales and blocks, and the cash it moved comes back.
 */
#[AsMessageHandler]
final readonly class DeleteDealCommandHandler
{
    public function __construct(
        private DealRepositoryInterface $dealRepository,
        private ManualOperationRepositoryInterface $operationRepository,
        private SyncedAccountGuard $syncedAccountGuard,
        private ManualJournal $journal,
    ) {
    }

    public function __invoke(DeleteDealCommand $command): void
    {
        $deal = $this->dealRepository->findById($command->id) ?? throw new NotFoundException(sprintf('Deal with id "%s" not found', $command->id));
        $account = $deal->getAccount();
        $this->syncedAccountGuard->assertManual($account);

        $lot = $this->journal->lotOf($deal);
        $opening = $this->operationRepository->findOpening($account, $lot)
            ?? throw new NotFoundException(sprintf('The purchase of deal "%s" is not in the journal', $command->id));
        $rest = $opening->getQuantity() - $deal->getQuantity();
        if ($rest <= 0) {
            $this->operationRepository->remove($opening, ...$this->operationRepository->findAboutLot($account, $lot));
        } elseif ($deal->getStatus() === DealStatus::Closed) {
            // The sale of a part may have covered other parts too; there is no telling what to take out of it
            throw DealCannotBeDeletedException::soldPartOfPurchase((int) $deal->getId());
        } else {
            $opening->correctOpening(
                type:        $opening->getType(),
                instrument:  $opening->getInstrument(),
                ticker:      (string) $opening->getTicker(),
                stockMarket: (string) $opening->getStockMarket(),
                quantity:    $rest,
                price:       $opening->getPrice(),
                targetPrice: $opening->getTargetPrice(),
            );
        }
        $this->journal->record($account);
    }
}
