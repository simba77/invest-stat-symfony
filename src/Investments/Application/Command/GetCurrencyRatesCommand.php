<?php

declare(strict_types=1);

namespace App\Investments\Application\Command;

use App\Investments\Application\Instruments\CurrencyRatesUpdater;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'currency:get-rates',
    description: 'Get Currency Rates',
)]
class GetCurrencyRatesCommand extends Command
{
    public function __construct(
        private readonly CurrencyRatesUpdater $currencyRatesUpdater
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->currencyRatesUpdater->updateCurrent();

        $io->success('Success');

        return Command::SUCCESS;
    }
}
