<?php

declare(strict_types=1);

namespace App\Investments\Application\Journal;

use App\Investments\Domain\Journal\OperationCannotBeChangedException;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CorrectSaleCommandHandler
{
    public function __construct(
        private ManualOperationFinder $finder,
        private ManualJournal $journal,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(CorrectSaleCommand $command): void
    {
        $sale = $this->finder->get($command->accountId, $command->operationId, $command->user);
        $now = $this->clock->now();
        if ($command->executedAt > $now) {
            throw OperationCannotBeChangedException::inFuture();
        }

        // Kept as the clock tells the time, the way the other operations are
        $this->journal->correctSale($sale, $command->price, $command->executedAt->setTimezone($now->getTimezone()));
    }
}
