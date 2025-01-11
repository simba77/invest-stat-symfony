<?php

declare(strict_types=1);

namespace App\Expenses\Infrastructure\Persistence\Repository;

use App\Expenses\Domain\Expense;
use App\Expenses\Domain\ExpenseRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Expense>
 */
class ExpenseRepository extends ServiceEntityRepository implements ExpenseRepositoryInterface
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

    public function save(Expense $expense): void
    {
        $em = $this->getEntityManager();
        $em->persist($expense);
        $em->flush();
    }

    public function remove(Expense $expense): void
    {
        $em = $this->getEntityManager();
        $em->remove($expense);
        $em->flush();
    }
}
