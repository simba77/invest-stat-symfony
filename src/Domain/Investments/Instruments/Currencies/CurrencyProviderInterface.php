<?php

declare(strict_types=1);

namespace App\Domain\Investments\Instruments\Currencies;

interface CurrencyProviderInterface
{
    /**
     * @return CurrencyRateInterface[]
     */
    public function getCurrencyRates(): array;
}
