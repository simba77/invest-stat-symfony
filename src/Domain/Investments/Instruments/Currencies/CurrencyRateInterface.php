<?php

declare(strict_types=1);

namespace App\Domain\Investments\Instruments\Currencies;

interface CurrencyRateInterface
{
    /**
     * Base currency e.g. RUB
     *
     * @return string
     */
    public function getBaseCurrency(): string;

    /**
     * Target currency e.g. USD
     *
     * @return string
     */
    public function getTargetCurrency(): string;

    /**
     * The exchange rate to the target currency. How much you need to spend the base currency to buy the target one.
     * E.g. 100 RUB per 1 USD
     *
     * @return string
     */
    public function getRate(): string;
}
