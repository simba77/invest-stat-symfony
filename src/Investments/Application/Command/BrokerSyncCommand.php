<?php

declare(strict_types=1);

namespace App\Investments\Application\Command;

use App\Investments\Application\BrokerSync\SyncBrokerAccountCommand;
use App\Investments\Domain\BrokerSync\BrokerAccountLinkRepositoryInterface;
use App\Shared\Domain\Bus\SyncCommandBusInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'broker:sync',
    description: 'Sync accounts linked to brokers',
)]
class BrokerSyncCommand extends Command
{
    public function __construct(
        private readonly BrokerAccountLinkRepositoryInterface $linkRepository,
        private readonly SyncCommandBusInterface $commandBus,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->addOption('account', null, InputOption::VALUE_REQUIRED, 'Sync only this account id');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $accountOption = $input->getOption('account');
        $accountIds = is_numeric($accountOption)
            ? [(int) $accountOption]
            : array_map(static fn ($link) => (int) $link->getAccount()->getId(), $this->linkRepository->findEnabled());

        if ($accountIds === []) {
            $io->success('No accounts are linked to brokers');

            return Command::SUCCESS;
        }

        $failed = false;
        foreach ($accountIds as $accountId) {
            try {
                $this->commandBus->dispatch(new SyncBrokerAccountCommand($accountId));
                $io->success(sprintf('Account %d is synced', $accountId));
            } catch (\Throwable $exception) {
                $failed = true;
                $io->error(sprintf('Account %d: %s', $accountId, $exception->getMessage()));
            }
        }

        return $failed ? Command::FAILURE : Command::SUCCESS;
    }
}
