<?php

declare(strict_types=1);

namespace App\Investments\Infrastructure\Persistence\Repository;

use App\Investments\Domain\Instruments\Bond;
use App\Investments\Domain\Instruments\BondRepositoryInterface;
use App\Shared\Infrastructure\Persistence\Doctrine\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Bond>
 */
class BondRepository extends ServiceEntityRepository implements BondRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bond::class);
    }

    #[\Override]
    public function findById(int $id): ?Bond
    {
        return $this->find($id);
    }

    #[\Override]
    public function findByTickerAndStockMarket(string $ticker, string $stockMarket): ?Bond
    {
        return $this->findOneBy(['ticker' => $ticker, 'stockMarket' => $stockMarket]);
    }

    #[\Override]
    public function findByTUid(string $tUid): ?Bond
    {
        // The same instrument can be listed on several exchanges; the first one listed wins
        return $this->findOneBy(['tUid' => $tUid], ['id' => 'ASC']);
    }
}
