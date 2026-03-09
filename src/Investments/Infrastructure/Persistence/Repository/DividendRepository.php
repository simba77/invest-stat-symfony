<?php

declare(strict_types=1);

namespace App\Investments\Infrastructure\Persistence\Repository;

use App\Investments\Domain\Operations\Dividend;
use App\Investments\Domain\Operations\DividendRepositoryInterface;
use App\Shared\Infrastructure\Persistence\Doctrine\ServiceEntityRepository;
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
    public function getPageByUserId(int $userId, int $offset, int $limit): array
    {
        return $this->createQueryBuilder('d')
            ->select(['d', 'a'])
            ->leftJoin('d.account', 'a')
            ->andWhere('IDENTITY(d.user) = :userId')
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
            ->andWhere('IDENTITY(d.user) = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function sumByTickerAndUserAndStockMarket(int $userId, string $ticker, string $stockMarket): string
    {
        $qb = $this->createQueryBuilder('d');

        $result = $qb
            ->select('COALESCE(SUM(d.amount), 0) as total')
            ->andWhere('d.user = :userId')
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
            ->leftJoin('d.account', 'a')
            ->andWhere('IDENTITY(d.user) = :userId')
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
}
