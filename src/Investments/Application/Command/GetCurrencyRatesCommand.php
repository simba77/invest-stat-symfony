<?php

declare(strict_types=1);

namespace App\Investments\Application\Command;

use App\Investments\Domain\Accounts\AccountCalculator;
use App\Investments\Domain\Instruments\Currencies\CurrencyProviderInterface;
use App\Investments\Domain\Instruments\CurrencyRate;
use Doctrine\ORM\EntityManagerInterface;
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
        private readonly CurrencyProviderInterface $currencyProvider,
        private readonly EntityManagerInterface $em,
        private readonly AccountCalculator $accountCalculator
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $currencyRateRepo = $this->em->getRepository(CurrencyRate::class);
        $rates = $this->currencyProvider->getCurrencyRates();
        foreach ($rates as $item) {
            $rate = $currencyRateRepo->findOneBy(['baseCurrency' => $item->getBaseCurrency(), 'targetCurrency' => $item->getTargetCurrency()]);
            if ($rate) {
                $rate->setRate($item->getRate());
            } else {
                $rate = new CurrencyRate($item->getBaseCurrency(), $item->getTargetCurrency(), $item->getRate());
            }
            $this->em->persist($rate);
            $this->em->flush();
        }

        $this->accountCalculator->recalculateBalanceForAllAccounts();

        $io->success('Success');

        return Command::SUCCESS;
    }
}
