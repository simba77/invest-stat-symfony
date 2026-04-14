<?php

declare(strict_types=1);

namespace App\Deposits\Infrastructure\Persistence\Repository;

use App\Deposits\Domain\Deposit;
use App\Deposits\Domain\DepositRepositoryInterface;
use App\Shared\Infrastructure\Persistence\Doctrine\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Deposit>
 */
class DepositRepository extends ServiceEntityRepository implements DepositRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Deposit::class);
    }

    #[\Override]
    public function getSumOfDepositsForUserId(int $userId): string
    {
        $data = $this->createQueryBuilder('d')
            ->andWhere('IDENTITY(d.user) = :userId')
            ->setParameter('userId', $userId)
            ->select("SUM(d.sum) as sum_of_deposits")
            ->getQuery()
            ->getOneOrNullResult();

        return (string) $data['sum_of_deposits'];
    }

    #[\Override]
    public function getDepositsForUserId(int $userId): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('IDENTITY(d.user) = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('d.date', Order::Descending->value)
            ->addOrderBy('d.id', Order::Descending->value)
            ->getQuery()
            ->getResult();
    }

    /** @return array<Deposit> */
    #[\Override]
    public function getDepositsPageForUserId(int $userId, int $offset, int $limit): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('IDENTITY(d.user) = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('d.date', Order::Descending->value)
            ->addOrderBy('d.id', Order::Descending->value)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    #[\Override]
    public function countByUserId(int $userId): int
    {
        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->andWhere('IDENTITY(d.user) = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    #[\Override]
    public function getDepositByIdAndUserId(int $id, int $userId): ?Deposit
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.id = :id')
            ->andWhere('IDENTITY(d.user) = :userId')
            ->setParameter('id', $id)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    #[\Override]
    public function save(Deposit $deposit): void
    {
        $em = $this->getEntityManager();
        $em->persist($deposit);
        $em->flush();
    }

    #[\Override]
    public function remove(Deposit $deposit): void
    {
        $em = $this->getEntityManager();
        $em->remove($deposit);
        $em->flush();
    }

    #[\Override]
    public function getTransactionsByAccount(int $userId): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $rows = $conn->executeQuery(
            'SELECT deposit_account_id, `sum`, date FROM deposits WHERE user_id = :userId ORDER BY deposit_account_id, date ASC, id ASC',
            ['userId' => $userId]
        )->fetchAllAssociative();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row['deposit_account_id']][] = ['sum' => $row['sum'], 'date' => $row['date']];
        }
        return $grouped;
    }

    #[\Override]
    public function getMonthlyStats(int $userId): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT DATE_FORMAT(date, '%Y.%m') as month,
                       SUM(CASE WHEN type = 1 THEN `sum` ELSE 0 END) as deposits,
                       SUM(CASE WHEN type = 2 THEN `sum` ELSE 0 END) as profit
                FROM deposits
                WHERE user_id = :userId
                GROUP BY DATE_FORMAT(date, '%Y.%m')
                ORDER BY month ASC";

        return $conn->executeQuery($sql, ['userId' => $userId])->fetchAllAssociative();
    }
}
