<?php

declare(strict_types=1);

namespace App\Investments\Infrastructure\Persistence\Repository;

use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Operations\Investment;
use App\Investments\Domain\Operations\InvestmentRepositoryInterface;
use App\Shared\Infrastructure\Persistence\Doctrine\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Investment>
 */
class InvestmentRepository extends ServiceEntityRepository implements InvestmentRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Investment::class);
    }

    /**
     * @param int $userId
     * @return array<int, array{investment: Investment, account_name: string}>
     */
    #[\Override]
    public function getByUserId(int $userId): array
    {
        return $this->createQueryBuilder('inv')
            ->select(['inv as investment'])
            ->leftJoin(Account::class, 'acc', Join::WITH, 'inv.account = acc.id')
            ->andWhere('inv.userId = :userId')
            ->addSelect(['acc.name as account_name'])
            ->setParameter('userId', $userId)
            ->orderBy('inv.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, array{investment: Investment, account_name: string}>
     */
    #[\Override]
    public function getPageByUserId(int $userId, int $offset, int $limit): array
    {
        return $this->createQueryBuilder('inv')
            ->select(['inv as investment'])
            ->leftJoin(Account::class, 'acc', Join::WITH, 'inv.account = acc.id')
            ->andWhere('inv.userId = :userId')
            ->addSelect(['acc.name as account_name'])
            ->setParameter('userId', $userId)
            ->orderBy('inv.date', 'DESC')
            ->addOrderBy('inv.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    #[\Override]
    public function countByUserId(int $userId): int
    {
        return (int) $this->createQueryBuilder('inv')
            ->select('COUNT(inv.id)')
            ->andWhere('inv.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    #[\Override]
    public function getSumByUserId(int $userId): string
    {
        return (string) $this->createQueryBuilder('inv')
            ->select('SUM(inv.sum) as allInvestments')
            ->where('inv.userId = :user_id')
            ->setParameter('user_id', $userId)
            ->getQuery()
            ->getOneOrNullResult()['allInvestments'];
    }

    /**
     * @return list<array{date: string, sum: string}>
     */
    #[\Override]
    public function getDailyCashFlowsByUserId(int $userId): array
    {
        return $this->getEntityManager()->getConnection()
            ->executeQuery(
                'SELECT DATE(inv.date) as date, SUM(inv.sum) as sum
                 FROM investments inv
                 WHERE inv.user_id = :userId
                 GROUP BY DATE(inv.date)
                 ORDER BY DATE(inv.date) ASC',
                ['userId' => $userId]
            )
            ->fetchAllAssociative();
    }
}
