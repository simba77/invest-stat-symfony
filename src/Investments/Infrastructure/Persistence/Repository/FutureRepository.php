<?php

declare(strict_types=1);

namespace App\Investments\Infrastructure\Persistence\Repository;

use App\Investments\Domain\Instruments\Future;
use App\Investments\Domain\Instruments\FutureRepositoryInterface;
use App\Shared\Infrastructure\Persistence\Doctrine\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Future>
 */
class FutureRepository extends ServiceEntityRepository implements FutureRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Future::class);
    }

    #[\Override]
    public function findByTickerAndStockMarket(string $ticker, string $stockMarket): ?Future
    {
        return $this->findOneBy(['ticker' => $ticker, 'stockMarket' => $stockMarket]);
    }

    #[\Override]
    public function findByTUid(string $tUid): ?Future
    {
        return $this->findOneBy(['tUid' => $tUid]);
    }
}
