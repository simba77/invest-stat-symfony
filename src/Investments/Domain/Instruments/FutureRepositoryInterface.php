<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments;

interface FutureRepositoryInterface
{
    public function findByTickerAndStockMarket(string $ticker, string $stockMarket): ?Future;

    public function findByTUid(string $tUid): ?Future;
}
