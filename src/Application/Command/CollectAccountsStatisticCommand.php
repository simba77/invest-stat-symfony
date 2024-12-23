<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Domain\Investments\Accounts\AccountCalculator;
use App\Domain\Investments\Analytics\Statistic;
use App\Infrastructure\Persistence\Repository\AccountRepository;
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
        private readonly AccountCalculator $accountCalculator,
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
            $currentValue = $this->accountCalculator->getAccountValue($account);
            $sumDeposits = $item['deposits_sum'] ?? '0';

            $stat = new Statistic(
                $account,
                new \DateTimeImmutable(),
                $account->getBalance(),
                $account->getUsdBalance(),
                $sumDeposits,
                $currentValue,
                bcsub($currentValue, $sumDeposits, 2)
            );

            $this->entityManager->persist($stat);
        }

        $this->entityManager->flush();

        $io->success('Success');

        return Command::SUCCESS;
    }
}
