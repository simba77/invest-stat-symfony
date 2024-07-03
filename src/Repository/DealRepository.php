<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Account;
use App\Entity\Bond;
use App\Entity\Deal;
use App\Entity\Future;
use App\Entity\Share;
use App\Entity\User;
use App\Request\DTO\Deals\DealsFilterRequestDTO;
use App\Services\Deals\DealStatus;
use Carbon\Carbon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Deal>
 *
 * @method Deal|null find($id, $lockMode = null, $lockVersion = null)
 * @method Deal|null findOneBy(array $criteria, array $orderBy = null)
 * @method Deal[]    findAll()
 * @method Deal[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DealRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Deal::class);
    }

    /**
     * @return array<int, array{deal: Deal}>
     */
    public function findForUser(?User $user): array
    {
        return $this->createQueryBuilder('d')
            ->select(
                [
                    'd as deal',

                    // Share
                    's.shortName shareName',
                    's.price sharePrice',
                    's.prevPrice sharePrevPrice',
                    's.currency shareCurrency',
                    's.type shareType',

                    // Bond
                    'b.shortName bondName',
                    'b.price bondPrice',
                    'b.prevPrice bondPrevPrice',
                    'b.currency bondCurrency',
                    'b.lotSize bondLotSize',

                    // Future
                    'f.shortName futureName',
                    'f.price futurePrice',
                    'f.prevPrice futurePrevPrice',
                    'f.currency futureCurrency',
                    'f.stepPrice futureStepPrice',
                    'f.lotSize futureLotSize',
                ]
            )
            ->andWhere('d.user = :user')
            ->andWhere('d.status != :status')
            ->setParameter('user', $user)
            ->setParameter('status', DealStatus::Closed)
            ->leftJoin(Share::class, 's', Join::WITH, 's.ticker = d.ticker AND s.stockMarket = d.stockMarket')
            ->leftJoin(Bond::class, 'b', Join::WITH, 'b.ticker = d.ticker AND b.stockMarket = d.stockMarket')
            ->leftJoin(Future::class, 'f', Join::WITH, 'f.ticker = d.ticker AND f.stockMarket = d.stockMarket')
            ->orderBy('d.status', 'ASC')
            ->addOrderBy('s.type', 'DESC')
            ->addOrderBy('s.currency', 'ASC')
            ->addOrderBy('d.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, array{deal: Deal}>
     */
    public function findForUserAndAccount(?User $user, Account $account): array
    {
        return $this->createQueryBuilder('d')
            ->select(
                [
                    'd as deal',

                    // Share
                    's.shortName shareName',
                    's.price sharePrice',
                    's.prevPrice sharePrevPrice',
                    's.currency shareCurrency',
                    's.type shareType',

                    // Bond
                    'b.shortName bondName',
                    'b.price bondPrice',
                    'b.prevPrice bondPrevPrice',
                    'b.currency bondCurrency',
                    'b.lotSize bondLotSize',

                    // Future
                    'f.shortName futureName',
                    'f.price futurePrice',
                    'f.prevPrice futurePrevPrice',
                    'f.currency futureCurrency',
                    'f.stepPrice futureStepPrice',
                    'f.lotSize futureLotSize',
                ]
            )
            ->andWhere('d.user = :user')
            ->andWhere('d.account = :account')
            ->andWhere('d.status != :status')
            ->setParameter('user', $user)
            ->setParameter('account', $account)
            ->setParameter('status', DealStatus::Closed)
            ->leftJoin(Share::class, 's', Join::WITH, 's.ticker = d.ticker AND s.stockMarket = d.stockMarket')
            ->leftJoin(Bond::class, 'b', Join::WITH, 'b.ticker = d.ticker AND b.stockMarket = d.stockMarket')
            ->leftJoin(Future::class, 'f', Join::WITH, 'f.ticker = d.ticker AND f.stockMarket = d.stockMarket')
            ->orderBy('d.status', 'ASC')
            ->addOrderBy('s.type', 'DESC')
            ->addOrderBy('s.currency', 'ASC')
            ->addOrderBy('d.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, array{deal: Deal}>
     */
    public function findForAccount(Account $account): array
    {
        return $this->createQueryBuilder('d')
            ->select(
                [
                    'd as deal',

                    // Share
                    's.shortName shareName',
                    's.price sharePrice',
                    's.prevPrice sharePrevPrice',
                    's.currency shareCurrency',
                    's.type shareType',

                    // Bond
                    'b.shortName bondName',
                    'b.price bondPrice',
                    'b.prevPrice bondPrevPrice',
                    'b.currency bondCurrency',
                    'b.lotSize bondLotSize',

                    // Future
                    'f.shortName futureName',
                    'f.price futurePrice',
                    'f.prevPrice futurePrevPrice',
                    'f.currency futureCurrency',
                    'f.stepPrice futureStepPrice',
                    'f.lotSize futureLotSize',
                ]
            )
            ->andWhere('d.account = :account')
            ->andWhere('d.status != :status')
            ->setParameter('account', $account)
            ->setParameter('status', DealStatus::Closed)
            ->leftJoin(Share::class, 's', Join::WITH, 's.ticker = d.ticker AND s.stockMarket = d.stockMarket')
            ->leftJoin(Bond::class, 'b', Join::WITH, 'b.ticker = d.ticker AND b.stockMarket = d.stockMarket')
            ->leftJoin(Future::class, 'f', Join::WITH, 'f.ticker = d.ticker AND f.stockMarket = d.stockMarket')
            ->orderBy('d.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getTickersByStockMarket(string $stockMarket)
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

    public function getAllTickersByStockMarket(string $stockMarket)
    {
        return $this->createQueryBuilder('d')
            ->select('d.ticker')
            ->where("d.stockMarket = :stock_market")
            ->groupBy('d.ticker')
            ->setParameter('stock_market', $stockMarket)
            ->getQuery()
            ->getResult();
    }

    public function getAllActiveFigi()
    {
        return $this->createQueryBuilder('d')
            ->select([
                         'd.ticker as ticker',
                         'd.stockMarket as stockMarket',
                         's.figi as figi'
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
     * @return array<int, array{deal: Deal}>
     */
    public function getClosedDealsForUserByFilter(User $user, ?DealsFilterRequestDTO $filter = null): array
    {
        $builder = $this->createQueryBuilder('d')
            ->select(
                [
                    'd as deal',

                    // Share
                    's.shortName shareName',
                    's.price sharePrice',
                    's.currency shareCurrency',
                    's.type shareType',

                    // Bond
                    'b.shortName bondName',
                    'b.price bondPrice',
                    'b.currency bondCurrency',
                    'b.lotSize bondLotSize',

                    // Future
                    'f.shortName futureName',
                    'f.price futurePrice',
                    'f.currency futureCurrency',
                    'f.stepPrice futureStepPrice',
                    'f.lotSize futureLotSize',
                ]
            )
            ->andWhere('d.user = :user')
            ->andWhere('d.status = :status')
            ->setParameter('user', $user)
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

        $builder->leftJoin(Share::class, 's', Join::WITH, 's.ticker = d.ticker AND s.stockMarket = d.stockMarket')
            ->leftJoin(Bond::class, 'b', Join::WITH, 'b.ticker = d.ticker AND b.stockMarket = d.stockMarket')
            ->leftJoin(Future::class, 'f', Join::WITH, 'f.ticker = d.ticker AND f.stockMarket = d.stockMarket')
            ->orderBy('d.status', 'ASC')
            ->addOrderBy('s.type', 'DESC')
            ->addOrderBy('s.currency', 'ASC')
            ->addOrderBy('d.id', 'ASC');

        return $builder->getQuery()->getResult();
    }
}
