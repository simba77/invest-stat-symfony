<?php

declare(strict_types=1);

namespace App\Services\MarketData\Currencies;

use App\Services\MarketData\Currencies\CurrencyRateInterface;

class CurrencyRateDTO implements CurrencyRateInterface
{
    public function __construct(
        public string $baseCurrency,
        public string $targetCurrency,
        public string $rate,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getBaseCurrency(): string
    {
        return $this->baseCurrency;
    }

    /**
     * @inheritDoc
     */
    public function getTargetCurrency(): string
    {
        return $this->targetCurrency;
    }

    /**
     * @inheritDoc
     */
    public function getRate(): string
    {
        return $this->rate;
    }
}
