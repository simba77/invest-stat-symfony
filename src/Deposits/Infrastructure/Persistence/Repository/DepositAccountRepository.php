<?php

declare(strict_types=1);

namespace App\Deposits\Infrastructure\Persistence\Repository;

use App\Deposits\Domain\DepositAccount;
use App\Deposits\Domain\DepositAccountRepositoryInterface;
use App\Domain\Shared\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DepositAccount>
 */
class DepositAccountRepository extends ServiceEntityRepository implements DepositAccountRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DepositAccount::class);
    }

    public function getForUser(User $user): array
    {
        return $this->findBy(['user' => $user]);
    }

    public function getWithSummary(User $user): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "select *,
           (select sum(`sum`) as aggregate
            from `deposits` as s
            where s.deposit_account_id = deposit_accounts.id) as balance,
           (select sum(`sum`) as aggregate
            from `deposits` as s
            where s.deposit_account_id = deposit_accounts.id
              and s.`type` = 2) as profit
           from deposit_accounts where deposit_accounts.user_id = :user";

        return $conn->executeQuery($sql, ['user' => $user->getId()])->fetchAllAssociative();
    }

    public function getByIdAndUser(int $id, User $user): ?DepositAccount
    {
        return $this->findOneBy(['id' => $id, 'user' => $user]);
    }

    public function save(DepositAccount $depositAccount): void
    {
        $em = $this->getEntityManager();
        $em->persist($depositAccount);
        $em->flush();
    }

    public function remove(DepositAccount $depositAccount): void
    {
        $em = $this->getEntityManager();
        $em->remove($depositAccount);
        $em->flush();
    }
}
