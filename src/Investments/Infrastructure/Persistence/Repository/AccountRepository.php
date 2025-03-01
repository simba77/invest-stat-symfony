<?php

declare(strict_types=1);

namespace App\Investments\Infrastructure\Persistence\Repository;

use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Accounts\AccountRepositoryInterface;
use App\Investments\Domain\Operations\Investment;
use App\Shared\Domain\User;
use App\Shared\Infrastructure\Persistence\Doctrine\ServiceEntityRepository;
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
    #[\Override]
    public function findByUserWithDeposits(User $user): array
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

    #[\Override]
    public function getByIdAndUser(int $id, User $user): ?Account
    {
        return $this->findOneBy(['id' => $id, 'userId' => $user->getId()]);
    }

    #[\Override]
    public function findByUser(User $user): array
    {
        return $this->findBy(['userId' => $user->getId()]);
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
     */
    #[\Override]
    public function findByIdAndUserWithDeposits(int $id, User $user): ?array
    {
        return $this->createQueryBuilder('a')
            ->select('a as account')
            ->andWhere('a.userId = :user_id')
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
    #[\Override]
    public function findAll(): array
    {
        return parent::findAll();
    }

    #[\Override]
    public function findById(int $id): ?Account
    {
        return $this->find($id);
    }

    #[\Override]
    public function save(Account $account): void
    {
        $em = $this->getEntityManager();
        $em->persist($account);
        $em->flush();
    }

    #[\Override]
    public function remove(Account $account): void
    {
        $em = $this->getEntityManager();
        $em->remove($account);
        $em->flush();
    }
}
