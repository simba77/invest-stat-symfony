<?php

declare(strict_types=1);

namespace App\Expenses\Infrastructure\Persistence\Repository;

use App\Expenses\Domain\Expense;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Expense>
 */
class ExpenseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Expense::class);
    }

    public function getSumForUser(int $userId): string
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.userId = :userId')
            ->setParameter('userId', $userId)
            ->select('sum(e.sum) as allExpenses')
            ->getQuery()
            ->getOneOrNullResult()['allExpenses'] ?? '0';
    }
}
