<?php

declare(strict_types=1);

namespace App\Investments\Application\Command;

use App\Investments\Application\Accounts\AccountBalanceCalculator;
use App\Investments\Domain\Analytics\Statistic;
use App\Investments\Infrastructure\Persistence\Repository\AccountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'accounts:save-stat',
    description: 'Collect accounts statistic',
)]
class CollectAccountsStatisticCommand extends Command
{
    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly AccountBalanceCalculator $accountBalanceCalculator,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $items = $this->accountRepository->findWithDeposits();
        foreach ($items as $item) {
            $account = $item['account'];
            $totalBalance = $this->accountBalanceCalculator->getTotalBalance($account);
            $sumDeposits = $item['deposits_sum'] ?? '0';

            $stat = new Statistic(
                $account,
                new \DateTimeImmutable(),
                $account->getBalance(),
                $account->getUsdBalance(),
                $sumDeposits,
                $totalBalance,
                bcsub($totalBalance, $sumDeposits, 2)
            );

            $this->entityManager->persist($stat);
        }

        $this->entityManager->flush();

        $io->success('Success');

        return Command::SUCCESS;
    }
}
