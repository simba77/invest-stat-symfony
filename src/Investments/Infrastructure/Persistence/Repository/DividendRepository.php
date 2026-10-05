<?php

declare(strict_types=1);

namespace App\Investments\Infrastructure\Persistence\Repository;

use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Operations\Dividend;
use App\Investments\Domain\Operations\DividendRepositoryInterface;
use App\Shared\Domain\User;
use App\Shared\Infrastructure\Persistence\Doctrine\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Dividend>
 */
class DividendRepository extends ServiceEntityRepository implements DividendRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dividend::class);
    }

    #[\Override]
    public function findAll(): array
    {
        return parent::findAll();
    }

    /**
     * @return array<Dividend>
     */
    #[\Override]
    public function findByUser(?User $user): array
    {
        return $this->createQueryBuilder('d')
            ->select(['d', 's'])
            ->leftJoin('d.share', 's')
            ->join('d.account', 'a')
            ->andWhere('a.userId = :user')
            ->setParameter('user', $user?->getId())
            ->orderBy('d.date', Order::Descending->value)
            ->addOrderBy('d.id', Order::Descending->value)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<Dividend>
     */
    #[\Override]
    public function getPageByUserId(int $userId, int $offset, int $limit): array
    {
        return $this->createQueryBuilder('d')
            ->select(['d', 'a'])
            ->join('d.account', 'a')
            ->andWhere('a.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('d.date', 'DESC')
            ->addOrderBy('d.id', 'DESC')
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
            ->join('d.account', 'a')
            ->andWhere('a.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function sumByTickerAndUserAndStockMarket(int $userId, string $ticker, string $stockMarket): string
    {
        $qb = $this->createQueryBuilder('d');

        $result = $qb
            ->select('COALESCE(SUM(d.amount), 0) as total')
            ->join('d.account', 'a')
            ->andWhere('a.userId = :userId')
            ->andWhere('d.ticker = :ticker')
            ->andWhere('d.stockMarket = :stockMarket')
            ->setParameter('userId', $userId)
            ->setParameter('ticker', $ticker)
            ->setParameter('stockMarket', $stockMarket)
            ->getQuery()
            ->getSingleScalarResult();

        return (string) $result;
    }

    /**
     * @return array<Dividend>
     */
    #[\Override]
    public function findByUserAndTickerAndStockMarket(int $userId, string $ticker, string $stockMarket): array
    {
        return $this->createQueryBuilder('d')
            ->select(['d', 'a'])
            ->join('d.account', 'a')
            ->andWhere('a.userId = :userId')
            ->andWhere('d.ticker = :ticker')
            ->andWhere('d.stockMarket = :stockMarket')
            ->setParameter('userId', $userId)
            ->setParameter('ticker', $ticker)
            ->setParameter('stockMarket', $stockMarket)
            ->orderBy('d.date', 'DESC')
            ->addOrderBy('d.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    #[\Override]
    public function sumByAccountAndCurrency(Account $account): array
    {
        /** @var list<array{currency: string|null, amount: numeric-string}> $rows */
        $rows = $this->createQueryBuilder('d')
            ->select('s.currency AS currency', 'SUM(d.amount) AS amount')
            ->leftJoin('d.share', 's')
            ->andWhere('d.account = :account')
            ->setParameter('account', $account->getId())
            ->groupBy('s.currency')
            ->getQuery()
            ->getArrayResult();

        $sums = [];
        foreach ($rows as $row) {
            // A dividend of a share the catalogue does not know is taken to be in roubles
            $currency = $row['currency'] ?? 'RUB';
            $sums[$currency] = bcadd($sums[$currency] ?? '0', $row['amount'], 4);
        }

        return $sums;
    }

    /**
     * @return list<Dividend>
     */
    #[\Override]
    public function findByAccount(Account $account): array
    {
        /** @var list<Dividend> */
        return $this->findBy(['account' => $account], ['id' => 'ASC']);
    }

    #[\Override]
    public function findByIdAndUser(int $id, User $user): ?Dividend
    {
        /** @var Dividend|null */
        return $this->createQueryBuilder('d')
            ->join('d.account', 'owner')
            ->andWhere('d.id = :id')
            ->andWhere('owner.userId = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user->getId())
            ->getQuery()
            ->getOneOrNullResult();
    }
}
