<?php

declare(strict_types=1);

namespace App\Investments\Infrastructure\Persistence\Repository;

use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Accounts\AccountRepositoryInterface;
use App\Investments\Domain\Operations\Investment;
use App\Shared\Domain\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Account>
 */
class AccountRepository extends ServiceEntityRepository implements AccountRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Account::class);
    }

    /**
     * @param User $user
     * @return array<int, array{account: Account, deposits_sum: string | null}>
     */
    public function findByUserIdWithDeposits(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->select('a as account')
            ->andWhere('a.userId = :val')
            ->setParameter('val', $user->getId())
            ->orderBy('a.sort', 'ASC')
            ->addSelect('(select sum(inv.sum) from ' . Investment::class . ' as inv where inv.account = a) as deposits_sum')
            ->getQuery()
            ->getResult();
    }

    public function getByIdAndUser(int $id, User $user): ?Account
    {
        return $this->findOneBy(['id' => $id, 'userId' => $user->getId()]);
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

    public function save(Account $account): void
    {
        $em = $this->getEntityManager();
        $em->persist($account);
        $em->flush();
    }

    public function remove(Account $account): void
    {
        $em = $this->getEntityManager();
        $em->remove($account);
        $em->flush();
    }
}
