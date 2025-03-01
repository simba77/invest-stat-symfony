<?php

declare(strict_types=1);

namespace App\Investments\Infrastructure\Persistence\Repository;

use App\Investments\Domain\Analytics\Statistic;
use App\Investments\Domain\Analytics\StatisticRepositoryInterface;
use App\Shared\Infrastructure\Persistence\Doctrine\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Statistic>
 */
class StatisticRepository extends ServiceEntityRepository implements StatisticRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Statistic::class);
    }

    /**
     * @return list<array{balance: string, usd_balance: string, investments: string, current_value: string, profit: string, date: string}>
     * @throws Exception
     */
    #[\Override]
    public function getLatestStatistic(): array
    {
        $connection = $this->getEntityManager()->getConnection();
        $resultSet = $connection->executeQuery(
            "SELECT * FROM statistic stat
            inner join (select account_id, MAX(date) as max_date from statistic group by account_id) s_date
            on stat.account_id = s_date.account_id and stat.date = s_date.max_date"
        );

        return $resultSet->fetchAllAssociative();
    }

    /**
     * @return list<array{balance: string, usd_balance: string, investments: string, current_value: string, profit: string, date: string}>
     * @throws Exception
     */
    #[\Override]
    public function getStatisticByYears(): array
    {
        $connection = $this->getEntityManager()->getConnection();
        $resultSet = $connection->executeQuery(
            "SELECT * FROM statistic stat
            inner join (select account_id, MIN(date) as max_date from statistic group by account_id, YEAR(statistic.date)) s_date
            on stat.account_id = s_date.account_id and stat.date = s_date.max_date ORDER BY stat.date"
        );

        return $resultSet->fetchAllAssociative();
    }
}
