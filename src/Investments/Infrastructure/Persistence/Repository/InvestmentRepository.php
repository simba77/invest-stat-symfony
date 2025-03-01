<?php

declare(strict_types=1);

namespace App\Investments\Infrastructure\Persistence\Repository;

use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Operations\Investment;
use App\Shared\Infrastructure\Persistence\Doctrine\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Investment>
 */
class InvestmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Investment::class);
    }

    /**
     * @param int $userId
     * @return array<int, array{investment: Investment, account_name: string}>
     */
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

    public function getSumByUserId(int $userId): string
    {
        return (string) $this->createQueryBuilder('inv')
            ->select('SUM(inv.sum) as allInvestments')
            ->where('inv.userId = :user_id')
            ->setParameter('user_id', $userId)
            ->getQuery()
            ->getOneOrNullResult()['allInvestments'];
    }
}
