<?php

declare(strict_types=1);

namespace App\Investments\Infrastructure\Persistence\Repository;

use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Operations\Investment;
use App\Investments\Domain\Operations\InvestmentRepositoryInterface;
use App\Shared\Domain\User;
use App\Shared\Infrastructure\Persistence\Doctrine\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Investment>
 */
class InvestmentRepository extends ServiceEntityRepository implements InvestmentRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Investment::class);
    }

    /**
     * @param int $userId
     * @return array<int, array{investment: Investment, account_name: string}>
     */
    #[\Override]
    public function getByUserId(int $userId): array
    {
        return $this->createQueryBuilder('inv')
            ->select(['inv as investment'])
            ->join('inv.account', 'acc')
            ->andWhere('acc.userId = :userId')
            ->addSelect(['acc.name as account_name'])
            ->setParameter('userId', $userId)
            ->orderBy('inv.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, array{investment: Investment, account_name: string}>
     */
    #[\Override]
    public function getPageByUserId(int $userId, int $offset, int $limit): array
    {
        return $this->createQueryBuilder('inv')
            ->select(['inv as investment'])
            ->join('inv.account', 'acc')
            ->andWhere('acc.userId = :userId')
            ->addSelect(['acc.name as account_name'])
            ->setParameter('userId', $userId)
            ->orderBy('inv.date', 'DESC')
            ->addOrderBy('inv.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    #[\Override]
    public function countByUserId(int $userId): int
    {
        return (int) $this->createQueryBuilder('inv')
            ->select('COUNT(inv.id)')
            ->join('inv.account', 'acc')
            ->andWhere('acc.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    #[\Override]
    public function getSumByUserId(int $userId): string
    {
        $data = $this->createQueryBuilder('inv')
            ->select('SUM(inv.sum) as allInvestments')
            ->join('inv.account', 'acc')
            ->where('acc.userId = :user_id')
            ->setParameter('user_id', $userId)
            ->getQuery()
            ->getOneOrNullResult();

        return (string) ($data['allInvestments'] ?? '0');
    }

    #[\Override]
    public function getGrossSumByUserId(int $userId): string
    {
        $data = $this->createQueryBuilder('inv')
            ->select('SUM(inv.sum) as grossInvestments')
            ->join('inv.account', 'acc')
            ->where('acc.userId = :user_id')
            ->andWhere('inv.sum > 0')
            ->setParameter('user_id', $userId)
            ->getQuery()
            ->getOneOrNullResult();

        return (string) ($data['grossInvestments'] ?? '0');
    }

    /**
     * @return list<array{date: string, sum: string}>
     */
    #[\Override]
    public function getDailyCashFlowsByUserId(int $userId): array
    {
        return $this->getEntityManager()->getConnection()
            ->executeQuery(
                'SELECT DATE(inv.date) as date, SUM(inv.sum) as sum
                 FROM investments inv
                 JOIN accounts acc ON acc.id = inv.account_id
                 WHERE acc.user_id = :userId
                 GROUP BY DATE(inv.date)
                 ORDER BY DATE(inv.date) ASC',
                ['userId' => $userId]
            )
            ->fetchAllAssociative();
    }

    #[\Override]
    public function sumByAccount(Account $account): string
    {
        $sum = $this->createQueryBuilder('inv')
            ->select('SUM(inv.sum)')
            ->andWhere('inv.account = :account')
            ->setParameter('account', $account->getId())
            ->getQuery()
            ->getSingleScalarResult();

        /** @var numeric-string */
        return is_numeric($sum) ? (string) $sum : '0';
    }

    /**
     * @return list<Investment>
     */
    #[\Override]
    public function findByAccount(Account $account): array
    {
        /** @var list<Investment> */
        return $this->findBy(['account' => $account], ['id' => 'ASC']);
    }

    #[\Override]
    public function findByIdAndUser(int $id, User $user): ?Investment
    {
        /** @var Investment|null */
        return $this->createQueryBuilder('inv')
            ->join('inv.account', 'owner')
            ->andWhere('inv.id = :id')
            ->andWhere('owner.userId = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user->getId())
            ->getQuery()
            ->getOneOrNullResult();
    }
}
