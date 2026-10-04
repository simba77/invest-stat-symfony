<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments\Currencies;

interface CurrencyProviderInterface
{
    /**
     * @return CurrencyRateInterface[]
     */
    public function getCurrencyRates(): array;

    /**
     * One rate per currency and exchange day, the last one set that day.
     *
     * @return list<CurrencyRateInterface>
     */
    public function getRateHistory(\DateTimeImmutable $from, \DateTimeImmutable $till): array;
}
