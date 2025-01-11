<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments\Currencies;

interface CurrencyProviderInterface
{
    /**
     * @return CurrencyRateInterface[]
     */
    public function getCurrencyRates(): array;
}
