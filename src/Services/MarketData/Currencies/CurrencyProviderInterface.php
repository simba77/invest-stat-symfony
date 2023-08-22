<?php

declare(strict_types=1);

namespace App\Services\MarketData\Currencies;

interface CurrencyProviderInterface
{
    /**
     * @return CurrencyRateInterface[]
     */
    public function getCurrencyRates(): array;
}
