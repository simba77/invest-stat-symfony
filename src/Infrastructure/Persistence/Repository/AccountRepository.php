<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Investments\Accounts\Account;
use App\Domain\Investments\Operations\Investment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Account>
 */
class AccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Account::class);
    }

    /**
     * @param int $userId
     * @return array<int, array{account: Account, deposits_sum: string | null}>
     */
    public function findByUserIdWithDeposits(int $userId): array
    {
        return $this->createQueryBuilder('a')
            ->select('a as account')
            ->andWhere('a.userId = :val')
            ->setParameter('val', $userId)
            ->orderBy('a.sort', 'ASC')
            ->addSelect('(select sum(inv.sum) from ' . Investment::class . ' as inv where inv.account = a) as deposits_sum')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, array{account: Account, deposits_sum: string | null}>
     */
    public function findWithDeposits(): array
    {
        return $this->createQueryBuilder('a')
            ->select('a as account')
            ->orderBy('a.sort', 'ASC')
            ->addSelect('(select sum(inv.sum) from ' . Investment::class . ' as inv where inv.account = a) as deposits_sum')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param int $id
     * @param int $userId
     * @return array{account: Account, deposits_sum: string} | null
     */
    public function findByUserAndIdWithDeposits(int $id, int $userId): ?array
    {
        return $this->createQueryBuilder('a')
            ->select('a as account')
            ->andWhere('a.userId = :user_id')
            ->andWhere('a.id = :id')
            ->setParameter('id', $id)
            ->setParameter('user_id', $userId)
            ->orderBy('a.sort', 'ASC')
            ->addSelect('(select sum(inv.sum) from ' . Investment::class . ' as inv where inv.account = a) as deposits_sum')
            ->getQuery()
            ->getOneOrNullResult();
    }
}
