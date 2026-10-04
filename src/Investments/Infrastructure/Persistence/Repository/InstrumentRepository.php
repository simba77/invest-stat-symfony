<?php

declare(strict_types=1);

namespace App\Investments\Infrastructure\Persistence\Repository;

use App\Investments\Domain\Instruments\Instrument;
use App\Investments\Domain\Instruments\InstrumentRepositoryInterface;
use App\Shared\Infrastructure\Persistence\Doctrine\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Instrument>
 */
class InstrumentRepository extends ServiceEntityRepository implements InstrumentRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Instrument::class);
    }

    #[\Override]
    public function findById(int $id): ?Instrument
    {
        return $this->find($id);
    }

    #[\Override]
    public function findByTickerAndStockMarket(string $ticker, string $stockMarket): ?Instrument
    {
        return $this->findOneBy(['ticker' => $ticker, 'stockMarket' => $stockMarket]);
    }
}
