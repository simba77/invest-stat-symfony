<?php

declare(strict_types=1);

namespace App\Services\MarketData\Currencies;

use App\Repository\CurrencyRateRepository;

class CurrencyService
{
    public function __construct(
        private readonly CurrencyRateRepository $currencyRateRepository
    ) {
    }

    /**
     * Get RUB per USD rate
     */
    public function getUSDRUBRate(): float
    {
        $currencyRate = $this->currencyRateRepository->findOneBy(['baseCurrency' => 'RUB', 'targetCurrency' => 'USD']);
        return $currencyRate?->getRate() ?? 0;
    }
}
