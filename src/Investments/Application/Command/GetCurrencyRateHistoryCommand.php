<?php

declare(strict_types=1);

namespace App\Investments\Application\Command;

use App\Investments\Application\Instruments\CurrencyRatesUpdater;
use Psr\Clock\ClockInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'currency:get-rate-history',
    description: 'Load the rates of past exchange days, which value past deals and payouts',
)]
final class GetCurrencyRateHistoryCommand extends Command
{
    public function __construct(
        private readonly CurrencyRatesUpdater $currencyRatesUpdater,
        private readonly ClockInterface $clock,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->addOption('from', null, InputOption::VALUE_REQUIRED, 'The first day: a date or a shift from today, e.g. "-7 days"', '2020-01-01');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $today = $this->clock->now()->setTime(0, 0);
        try {
            $from = $today->modify($input->getOption('from'));
        } catch (\DateMalformedStringException) {
            $io->error('The --from option is neither a date nor a shift');

            return Command::INVALID;
        }

        $changed = $this->currencyRatesUpdater->loadHistory($from, $today);
        $io->success(sprintf('Days added or changed: %d', $changed));

        return Command::SUCCESS;
    }
}
