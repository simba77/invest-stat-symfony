<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Deposits\DepositAccount;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DepositAccount>
 *
 * @method DepositAccount|null find($id, $lockMode = null, $lockVersion = null)
 * @method DepositAccount|null findOneBy(array $criteria, array $orderBy = null)
 * @method DepositAccount[]    findAll()
 * @method DepositAccount[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DepositAccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DepositAccount::class);
    }

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
