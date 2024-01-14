<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Account;
use App\Entity\Deal;
use App\Entity\Deposit;
use App\Entity\DepositAccount;
use App\Entity\Investment;
use App\Entity\Statistic;
use App\Entity\User;
use App\Services\Deals\DealStatus;
use App\Services\Deals\DealType;
use Carbon\Carbon;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'convert-old-data',
    description: 'Convert the old database to the new',
)]
class ConvertOldDataCommand extends Command
{
    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->convertAccounts();
        $this->convertDeals();
        $this->convertInvestments();
        $this->convertDeposits();
        $this->convertStatistic();

        $io->success('Success');

        return Command::SUCCESS;
    }

    private function convertAccounts(): void
    {
        /** @var Connection $connection */
        $connection = $this->managerRegistry->getConnection('old_database');

        $accounts = $connection->executeQuery('SELECT * FROM `accounts`')->fetchAllAssociative();
        foreach ($accounts as $account) {
            $newAcc = new Account(
                1,
                $account['name'],
                (string) $account['balance'],
                (string) $account['usd_balance'],
                (string) $account['commission'],
                (string) $account['futures_commission'],
                $account['sort'],
            );

            $newAcc->setId($account['id']);
            $this->entityManager->persist($newAcc);
        }

        $this->entityManager->flush();
    }

    private function convertDeals(): void
    {
        /** @var Connection $connection */
        $connection = $this->managerRegistry->getConnection('old_database');

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => 1]);
        $accountRepo = $this->entityManager->getRepository(Account::class);

        $assets = $connection->executeQuery('SELECT assets.*, accounts.name as account_name FROM `assets` left join accounts ON assets.account_id = accounts.id')->fetchAllAssociative();
        foreach ($assets as $asset) {
            $acc = $accountRepo->findOneBy(['name' => $asset['account_name']]);

            $dealStatus = DealStatus::Active;
            if ($asset['status'] === 1) {
                $dealStatus = DealStatus::Closed;
            } elseif ($asset['blocked']) {
                $dealStatus = DealStatus::Blocked;
            }

            $newDeal = new Deal(
                $user,
                $acc,
                $asset['ticker'],
                $asset['stock_market'],
                $dealStatus,
                $asset['type'] === 1 ? DealType::Short : DealType::Long,
                $asset['quantity'],
                $asset['buy_price'],
                $asset['target_price'],
                $asset['sell_price'],
            );

            $this->entityManager->persist($newDeal);
        }

        $this->entityManager->flush();
    }

    public function convertInvestments(): void
    {
        /** @var Connection $connection */
        $connection = $this->managerRegistry->getConnection('old_database');

        $accountRepo = $this->entityManager->getRepository(Account::class);

        $items = $connection->executeQuery('SELECT deposits.*, accounts.name as account_name FROM `deposits` left join accounts ON deposits.account_id = accounts.id')->fetchAllAssociative();
        foreach ($items as $item) {
            $acc = $accountRepo->findOneBy(['name' => $item['account_name']]);
            $investment = new Investment(
                $item['sum'],
                Carbon::parse($item['date'])->toImmutable(),
                $acc,
                1
            );

            $this->entityManager->persist($investment);
        }

        $this->entityManager->flush();
    }

    public function convertDeposits(): void
    {
        /** @var Connection $connection */
        $connection = $this->managerRegistry->getConnection('old_database');
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => 1]);
        $items = $connection->executeQuery('SELECT * FROM `saving_accounts`')->fetchAllAssociative();
        foreach ($items as $item) {
            $depositAccount = new DepositAccount(
                $item['name'],
                $user
            );
            $this->entityManager->persist($depositAccount);
        }

        $this->entityManager->flush();


        $accountRepo = $this->entityManager->getRepository(DepositAccount::class);
        $items = $connection->executeQuery('SELECT savings.*, saving_accounts.name as account_name FROM `savings` left join saving_accounts ON savings.saving_account_id = saving_accounts.id')->fetchAllAssociative();
        foreach ($items as $item) {
            $acc = $accountRepo->findOneBy(['name' => $item['account_name']]);
            $deposit = new Deposit(
                $item['sum'],
                $item['type'],
                $user,
                $acc,
                Carbon::parse($item['created_at'])->toImmutable()
            );

            $this->entityManager->persist($deposit);
        }

        $this->entityManager->flush();
    }

    public function convertStatistic(): void
    {
        /** @var Connection $connection */
        $connection = $this->managerRegistry->getConnection('old_database');

        $accountRepo = $this->entityManager->getRepository(Account::class);

        $items = $connection->executeQuery('SELECT statistic.*, accounts.name as account_name FROM `statistic` left join accounts ON statistic.account_id = accounts.id')->fetchAllAssociative();
        foreach ($items as $item) {
            $acc = $accountRepo->findOneBy(['name' => $item['account_name']]);

            $stat = new Statistic(
                $acc,
                Carbon::parse($item['date']),
                $item['balance'],
                $item['usd_balance'],
                $item['deposits'],
                $item['current'],
                $item['profit'],
            );

            $this->entityManager->persist($stat);
        }

        $this->entityManager->flush();
    }
}
