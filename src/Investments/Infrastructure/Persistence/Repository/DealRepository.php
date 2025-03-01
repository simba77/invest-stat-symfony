<?php

declare(strict_types=1);

namespace App\Investments\Infrastructure\Persistence\Repository;

use App\Investments\Application\Request\DTO\Operations\DealsFilterRequestDTO;
use App\Investments\Domain\Instruments\Share;
use App\Investments\Domain\Operations\Deal;
use App\Investments\Domain\Operations\DealRepositoryInterface;
use App\Investments\Domain\Operations\Deals\DealStatus;
use App\Shared\Infrastructure\Persistence\Doctrine\ServiceEntityRepository;
use Carbon\Carbon;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Deal>
 */
class DealRepository extends ServiceEntityRepository implements DealRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Deal::class);
    }

    /**
     * @return array<int, Deal>
     */
    #[\Override]
    public function findByUserId(int $userId): array
    {
        return $this->createQueryBuilder('d')
            ->select(['d', 's', 'b', 'f'])
            ->leftJoin('d.share', 's')
            ->leftJoin('d.bond', 'b')
            ->leftJoin('d.future', 'f')
            ->andWhere('d.user = :userId')
            ->andWhere('d.status != :status')
            ->setParameter('userId', $userId)
            ->setParameter('status', DealStatus::Closed)
            ->orderBy('d.status', 'ASC')
            ->addOrderBy('s.type', 'DESC')
            ->addOrderBy('s.currency', 'ASC')
            ->addOrderBy('d.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    #[\Override]
    public function findById(int $id): ?Deal
    {
        return $this->find($id);
    }

    /**
     * @return array<int, Deal>
     */
    #[\Override]
    public function findForUserAndAccount(int $userId, int $accountId): array
    {
        return $this->createQueryBuilder('d')
            ->select(['d', 's', 'b', 'f'])
            ->leftJoin('d.share', 's')
            ->leftJoin('d.bond', 'b')
            ->leftJoin('d.future', 'f')
            ->andWhere('d.user = :userId')
            ->andWhere('d.account = :accountId')
            ->andWhere('d.status != :status')
            ->setParameter('userId', $userId)
            ->setParameter('accountId', $accountId)
            ->setParameter('status', DealStatus::Closed)
            ->orderBy('d.status', 'ASC')
            ->addOrderBy('s.type', 'DESC')
            ->addOrderBy('s.currency', 'ASC')
            ->addOrderBy('d.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, Deal>
     */
    #[\Override]
    public function findForAccount(int $accountId): array
    {
        return $this->createQueryBuilder('d')
            ->select(['d', 's', 'b', 'f'])
            ->leftJoin('d.share', 's')
            ->leftJoin('d.bond', 'b')
            ->leftJoin('d.future', 'f')
            ->andWhere('d.account = :accountId')
            ->andWhere('d.status != :status')
            ->setParameter('accountId', $accountId)
            ->setParameter('status', DealStatus::Closed)
            ->orderBy('d.status', 'ASC')
            ->addOrderBy('s.type', 'DESC')
            ->addOrderBy('s.currency', 'ASC')
            ->addOrderBy('d.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<array{ticker: string}> */
    public function getTickersByStockMarket(string $stockMarket): array
    {
        return $this->createQueryBuilder('d')
            ->select('d.ticker')
            ->where("d.stockMarket = :stock_market")
            ->andWhere("d.status != :status")
            ->groupBy('d.ticker')
            ->setParameter('stock_market', $stockMarket)
            ->setParameter('status', DealStatus::Closed)
            ->getQuery()
            ->getResult();
    }

    /** @return list<array{ticker: string}> */
    public function getAllTickersByStockMarket(string $stockMarket): array
    {
        return $this->createQueryBuilder('d')
            ->select('d.ticker')
            ->where("d.stockMarket = :stock_market")
            ->groupBy('d.ticker')
            ->setParameter('stock_market', $stockMarket)
            ->getQuery()
            ->getResult();
    }

    /** @return list<array{ticker: string, stockMarket: string, figi: string}> */
    public function getAllActiveFigi(): array
    {
        return $this->createQueryBuilder('d')
            ->select([
                         'd.ticker as ticker',
                         'd.stockMarket as stockMarket',
                         's.figi as figi',
                     ])
            ->leftJoin(Share::class, 's', Join::WITH, 's.ticker = d.ticker AND s.stockMarket = d.stockMarket')
            ->andWhere("d.status != :status")
            ->andWhere("s.figi is not null")
            ->groupBy('d.ticker')
            ->setParameter('status', DealStatus::Closed)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, Deal>
     */
    #[\Override]
    public function getClosedDealsForUserByFilter(int $userId, ?DealsFilterRequestDTO $filter = null): array
    {
        $builder = $this->createQueryBuilder('d')
            ->select(['d', 's', 'b', 'f'])
            ->leftJoin('d.share', 's')
            ->leftJoin('d.bond', 'b')
            ->leftJoin('d.future', 'f')
            ->andWhere('d.user = :userId')
            ->andWhere('d.status = :status')
            ->setParameter('userId', $userId)
            ->setParameter('status', DealStatus::Closed);

        if ($filter) {
            if (! empty($filter->startDate)) {
                $builder->andWhere('d.closingDate >= :closing_date_start')
                    ->setParameter('closing_date_start', Carbon::createFromFormat('d.m.Y', $filter->startDate)->setTime(0, 0));
            }
            if (! empty($filter->endDate)) {
                $builder->andWhere('d.closingDate <= :closing_date_end')
                    ->setParameter('closing_date_end', Carbon::createFromFormat('d.m.Y', $filter->endDate)->setTime(23, 59, 59));
            }
        }

        $builder->orderBy('d.status', 'ASC')
            ->addOrderBy('s.type', 'DESC')
            ->addOrderBy('s.currency', 'ASC')
            ->addOrderBy('d.id', 'ASC');

        return $builder->getQuery()->getResult();
    }

    #[\Override]
    public function save(Deal $deal): void
    {
        $em = $this->getEntityManager();
        $em->persist($deal);
        $em->flush();
    }

    #[\Override]
    public function remove(Deal $deal): void
    {
        $em = $this->getEntityManager();
        $em->remove($deal);
        $em->flush();
    }
}
