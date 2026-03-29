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
     * @param int $userId
     * @return list<array{balance: string, usd_balance: string, investments: string, current_value: string, profit: string, date: string}>
     * @throws Exception
     */
    #[\Override]
    public function getLatestStatistic(int $userId): array
    {
        $connection = $this->getEntityManager()->getConnection();
        $resultSet = $connection->executeQuery(
            "SELECT stat.* FROM statistic stat
            inner join (
                select s.account_id, MAX(s.date) as max_date
                from statistic s
                inner join accounts a on a.id = s.account_id
                where a.user_id = :userId
                group by s.account_id
            ) s_date
            on stat.account_id = s_date.account_id and stat.date = s_date.max_date",
            ['userId' => $userId]
        );

        return $resultSet->fetchAllAssociative();
    }

    /**
     * @param int $userId
     * @return list<array{balance: string, usd_balance: string, investments: string, current_value: string, profit: string, date: string}>
     * @throws Exception
     */
    #[\Override]
    public function getStatisticByYears(int $userId): array
    {
        $connection = $this->getEntityManager()->getConnection();
        $resultSet = $connection->executeQuery(
            "SELECT stat.* FROM statistic stat
            inner join (
                select s.account_id, MIN(s.date) as min_date
                from statistic s
                inner join accounts a on a.id = s.account_id
                where a.user_id = :userId
                group by s.account_id, YEAR(s.date)
            ) s_date
            on stat.account_id = s_date.account_id and stat.date = s_date.min_date
            ORDER BY stat.date",
            ['userId' => $userId]
        );

        return $resultSet->fetchAllAssociative();
    }
}
