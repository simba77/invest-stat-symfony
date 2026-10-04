<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments;

interface CurrencyRateRepositoryInterface
{
    public function findLatest(string $baseCurrency, string $targetCurrency): ?CurrencyRate;

    public function findOnDate(string $baseCurrency, string $targetCurrency, \DateTimeImmutable $date): ?CurrencyRate;

    /**
     * Every known day of the pair, in date order.
     *
     * @return list<array{date: \DateTimeImmutable, rate: numeric-string}>
     */
    public function findHistory(string $baseCurrency, string $targetCurrency): array;

    public function save(CurrencyRate ...$rates): void;
}
