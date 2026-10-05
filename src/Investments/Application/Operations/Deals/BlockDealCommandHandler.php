<?php

declare(strict_types=1);

namespace App\Investments\Application\Operations\Deals;

use App\Investments\Application\BrokerSync\SyncedAccountGuard;
use App\Investments\Application\Journal\ManualJournal;
use App\Investments\Domain\Journal\ManualOperation;
use App\Investments\Domain\Operations\DealRepositoryInterface;
use App\Shared\Infrastructure\Symfony\NotFoundException;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Records from now on that the deal cannot be traded, or can be again: sales by quantity pass a blocked deal by.
 */
#[AsMessageHandler]
final readonly class BlockDealCommandHandler
{
    public function __construct(
        private DealRepositoryInterface $dealRepository,
        private SyncedAccountGuard $syncedAccountGuard,
        private ManualJournal $journal,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(BlockDealCommand $command): void
    {
        $deal = $this->dealRepository->findById($command->id) ?? throw new NotFoundException(sprintf('Deal with id "%s" not found', $command->id));
        $account = $deal->getAccount();
        $this->syncedAccountGuard->assertManual($account);

        $lot = $this->journal->lotOf($deal);
        $this->journal->record($account, ManualOperation::block($account, $this->clock->now(), $lot, $command->blocked));
    }
}
