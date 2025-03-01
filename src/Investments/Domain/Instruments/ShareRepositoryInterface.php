<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments;

interface ShareRepositoryInterface
{
    public function findByTickerAndStockMarket(string $ticker, string $stockMarket): ?Share;
}
