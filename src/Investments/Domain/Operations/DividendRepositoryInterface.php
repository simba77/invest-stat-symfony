<?php

declare(strict_types=1);

namespace App\Investments\Domain\Operations;

use App\Shared\Domain\User;

interface DividendRepositoryInterface
{
    /**
     * @return array<Dividend>
     */
    public function findAll(): array;

    /**
     * @return array<Dividend>
     */
    public function findByUser(?User $user): array;

    /**
     * @return array<Dividend>
     */
    public function getPageByUserId(int $userId, int $offset, int $limit): array;

    public function countByUserId(int $userId): int;

    public function sumByTickerAndUserAndStockMarket(int $userId, string $ticker, string $stockMarket): string;

    /**
     * @return array<Dividend>
     */
    public function findByUserAndTickerAndStockMarket(int $userId, string $ticker, string $stockMarket): array;
}
