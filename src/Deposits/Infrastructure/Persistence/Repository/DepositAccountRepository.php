<?php

declare(strict_types=1);

namespace App\Deposits\Infrastructure\Persistence\Repository;

use App\Deposits\Domain\DepositAccount;
use App\Deposits\Domain\DepositAccountRepositoryInterface;
use App\Shared\Domain\User;
use App\Shared\Infrastructure\Persistence\Doctrine\ServiceEntityRepository;
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

    #[\Override]
    public function getForUser(User $user): array
    {
        return $this->findBy(['user' => $user]);
    }

    #[\Override]
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

    #[\Override]
    public function getAccountStats(User $user): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT da.id,
                       da.name,
                       COALESCE(SUM(d.sum), 0) as balance,
                       COALESCE(SUM(CASE WHEN d.type = 2 THEN d.sum ELSE 0 END), 0) as profit,
                       COALESCE(SUM(CASE WHEN d.type = 1 AND d.sum > 0 THEN d.sum ELSE 0 END), 0) as gross_invested,
                       MIN(d.date) as start_date
                FROM deposit_accounts da
                LEFT JOIN deposits d ON d.deposit_account_id = da.id
                WHERE da.user_id = :userId
                GROUP BY da.id, da.name
                ORDER BY balance DESC, profit DESC";

        return $conn->executeQuery($sql, ['userId' => $user->getId()])->fetchAllAssociative();
    }

    #[\Override]
    public function getByIdAndUser(int $id, User $user): ?DepositAccount
    {
        return $this->findOneBy(['id' => $id, 'user' => $user]);
    }

    #[\Override]
    public function save(DepositAccount $depositAccount): void
    {
        $em = $this->getEntityManager();
        $em->persist($depositAccount);
        $em->flush();
    }

    #[\Override]
    public function remove(DepositAccount $depositAccount): void
    {
        $em = $this->getEntityManager();
        $em->remove($depositAccount);
        $em->flush();
    }
}
