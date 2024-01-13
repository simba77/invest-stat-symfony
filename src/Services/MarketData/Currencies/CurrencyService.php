<?php

declare(strict_types=1);

namespace App\Services\MarketData\Currencies;

use App\Repository\CurrencyRateRepository;

class CurrencyService
{
    public function __construct(
        private readonly CurrencyRateRepository $currencyRateRepository,
        private ?float $usdRubRate = null
    ) {
    }

    /**
     * Get RUB per USD rate
     */
    public function getUSDRUBRate(): float
    {
        if ($this->usdRubRate !== null) {
            return $this->usdRubRate;
        }
        $currencyRate = $this->currencyRateRepository->findOneBy(['baseCurrency' => 'RUB', 'targetCurrency' => 'USD']);
        $this->usdRubRate = $currencyRate?->getRate() ?? 0;
        return $this->usdRubRate;
    }
}
