<?php

declare(strict_types=1);

namespace App\Investments\Domain\Operations;

interface DividendRepositoryInterface
{
    /**
     * @return array<Dividend>
     */
    public function findAll(): array;

    public function sumByTickerAndUserAndStockMarket(int $userId, string $ticker, string $stockMarket): string;
}
