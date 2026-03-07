<?php

declare(strict_types=1);

namespace App\Investments\Domain\Operations;

interface DividendRepositoryInterface
{
    /**
     * @return array<Dividend>
     */
    public function findAll(): array;

    /**
     * @return array<Dividend>
     */
    public function getPageByUserId(int $userId, int $offset, int $limit): array;

    public function countByUserId(int $userId): int;

    public function sumByTickerAndUserAndStockMarket(int $userId, string $ticker, string $stockMarket): string;
}
