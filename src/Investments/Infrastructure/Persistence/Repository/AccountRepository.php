<?php

declare(strict_types=1);

namespace App\Investments\Infrastructure\Persistence\Repository;

use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Accounts\AccountRepositoryInterface;
use App\Investments\Domain\Operations\Coupon;
use App\Investments\Domain\Operations\Deal;
use App\Investments\Domain\Operations\Dividend;
use App\Investments\Domain\Operations\Investment;
use App\Shared\Domain\User;
use App\Shared\Infrastructure\Persistence\Doctrine\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
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
     * @param int $userId
     * @return array<int, array{account: Account, deposits_sum: string | null}>
     */
    public function findByUserWithDeposits(int $userId): array
    {
        return $this->createQueryBuilder('a')
            ->select('a as account')
            ->andWhere('IDENTITY(a.user) = :val')
            ->setParameter('val', $userId)
            ->orderBy('a.sort', 'ASC')
            ->addSelect('(select sum(inv.sum) from ' . Investment::class . ' as inv where inv.account = a) as deposits_sum')
            ->getQuery()
            ->getResult();
    }

    public function getByIdAndUser(int $id, User $user): ?Account
    {
        return $this->findOneBy(['id' => $id, 'user' => $user]);
    }

    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user]);
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
     * @return array{account: Account, deposits_sum: string} | null
     * @throws NonUniqueResultException
     */
    public function findByIdAndUserWithDeposits(int $id, User $user): ?array
    {
        return $this->createQueryBuilder('a')
            ->select('a as account')
            ->andWhere('IDENTITY(a.user) = :user_id')
            ->andWhere('a.id = :id')
            ->setParameter('id', $id)
            ->setParameter('user_id', $user->getId())
            ->orderBy('a.sort', 'ASC')
            ->addSelect('(select sum(inv.sum) from ' . Investment::class . ' as inv where inv.account = a) as deposits_sum')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<Account>
     */
    public function findAll(): array
    {
        return parent::findAll();
    }

    public function findById(int $id): ?Account
    {
        return $this->find($id);
    }

    public function hasRecords(Account $account): bool
    {
        foreach ([Deal::class, Investment::class, Dividend::class, Coupon::class] as $class) {
            $found = $this->getEntityManager()->createQueryBuilder()
                ->select('r.id')
                ->from($class, 'r')
                ->andWhere('IDENTITY(r.account) = :account')
                ->setParameter('account', $account->getId())
                ->setMaxResults(1)
                ->getQuery()
                ->getScalarResult();
            if ($found !== []) {
                return true;
            }
        }

        return false;
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
