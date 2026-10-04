<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments;

interface FutureRepositoryInterface
{
    public function findById(int $id): ?Future;

    public function findByTickerAndStockMarket(string $ticker, string $stockMarket): ?Future;

    public function findByTUid(string $tUid): ?Future;

    public function findByTicker(string $ticker): ?Future;

    /**
     * @return list<Future> the futures valued with a multiplier of the owner, by ticker
     */
    public function findWithMultiplier(): array;

    public function save(Future $future): void;
}
