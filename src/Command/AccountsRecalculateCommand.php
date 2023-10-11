<?php

declare(strict_types=1);

namespace App\Command;

use App\Services\AccountCalculator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'accounts:recalculate',
    description: 'Recalculate the current sum for each account',
)]
class AccountsRecalculateCommand extends Command
{
    public function __construct(
        private readonly AccountCalculator $accountCalculator
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->accountCalculator->recalculateBalanceForAllAccounts();
        $io->success('The current sum of assets has been successfully recalculated');

        return Command::SUCCESS;
    }
}
