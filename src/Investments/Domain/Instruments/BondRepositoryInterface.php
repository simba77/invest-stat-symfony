<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments;

interface BondRepositoryInterface
{
    public function findByTickerAndStockMarket(string $ticker, string $stockMarket): ?Bond;

    public function findByTUid(string $tUid): ?Bond;
}
