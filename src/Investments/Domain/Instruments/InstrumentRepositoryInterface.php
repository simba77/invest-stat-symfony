<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments;

interface InstrumentRepositoryInterface
{
    public function findById(int $id): ?Instrument;

    /**
     * An instrument of any kind: a ticker is unique on its exchange.
     */
    public function findByTickerAndStockMarket(string $ticker, string $stockMarket): ?Instrument;
}
