<?php

declare(strict_types=1);

namespace App\Investments\Infrastructure\Persistence\Repository;

use App\Investments\Domain\Instruments\Share;
use App\Investments\Domain\Instruments\ShareRepositoryInterface;
use App\Shared\Infrastructure\Persistence\Doctrine\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Share>
 */
class ShareRepository extends ServiceEntityRepository implements ShareRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Share::class);
    }

    public function findById(int $id): ?Share
    {
        return $this->find($id);
    }

    public function findByTickerAndStockMarket(string $ticker, string $stockMarket): ?Share
    {
        return $this->findOneBy(['ticker' => $ticker, 'stockMarket' => $stockMarket]);
    }

    public function findByTUid(string $tUid): ?Share
    {
        return $this->findOneBy(['tUid' => $tUid]);
    }
}
