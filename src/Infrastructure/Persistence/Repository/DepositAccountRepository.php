<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Deposits\DepositAccount;
use App\Domain\Shared\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DepositAccount>
 */
class DepositAccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DepositAccount::class);
    }

    /** @return list<array{id: int, name: string, balance: string, profit: string}> */
    public function getDepositAccountsWithSummary(User $user): array
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
}
