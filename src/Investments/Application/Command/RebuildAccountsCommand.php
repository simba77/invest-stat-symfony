<?php

declare(strict_types=1);

namespace App\Investments\Application\Command;

use App\Investments\Application\BrokerSync\SyncedAccountGuard;
use App\Investments\Application\Journal\ManualJournal;
use App\Investments\Domain\Accounts\AccountRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'accounts:rebuild',
    description: 'Rebuild the deals and cash of manual accounts from their journals, starting the journals that have not started',
)]
final class RebuildAccountsCommand extends Command
{
    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository,
        private readonly SyncedAccountGuard $syncedAccountGuard,
        private readonly ManualJournal $journal,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $rebuilt = 0;
        foreach ($this->accountRepository->findAll() as $account) {
            if ($this->syncedAccountGuard->isSynced($account)) {
                continue;
            }
            $this->journal->rebuild($account);
            $rebuilt++;
        }
        $io->success(sprintf('Manual accounts rebuilt: %d', $rebuilt));

        return Command::SUCCESS;
    }
}
