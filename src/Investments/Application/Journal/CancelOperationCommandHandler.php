<?php

declare(strict_types=1);

namespace App\Investments\Application\Journal;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CancelOperationCommandHandler
{
    public function __construct(
        private ManualOperationFinder $finder,
        private ManualJournal $journal,
    ) {
    }

    public function __invoke(CancelOperationCommand $command): void
    {
        $this->journal->cancel($this->finder->get($command->accountId, $command->operationId, $command->user));
    }
}
