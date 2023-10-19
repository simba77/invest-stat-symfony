<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Account;
use App\Entity\Investment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Investment>
 *
 * @method Investment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Investment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Investment[]    findAll()
 * @method Investment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
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
            ->orderBy('inv.date')
            ->getQuery()
            ->getResult();
    }

    public function getSumByUserId(int $userId)
    {
        return $this->createQueryBuilder('inv')
            ->select('SUM(inv.sum) as allInvestments')
            ->where('inv.userId = :user_id')
            ->setParameter('user_id', $userId)
            ->getQuery()
            ->getOneOrNullResult()['allInvestments'];
    }
}
