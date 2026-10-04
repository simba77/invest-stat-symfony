<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\Compiler;

use App\Investments\Application\Response\DTO\Operations\Deals\DealCurrencyDTO;
use App\Investments\Domain\Instruments\Currencies\Currency;
use App\Investments\Domain\Operations\Deal;

class DealListCurrencies
{
    /** @var array<string, DealCurrencyDTO> */
    private array $currencies = [];

    public function add(Deal $deal): DealCurrencyDTO
    {
        $currencyCode = match (true) {
            $deal->getShare() !== null => $deal->getShare()->getCurrency(),
            $deal->getBond() !== null => $deal->getBond()->getCurrency(),
            $deal->getFuture() !== null => $deal->getFuture()->getCurrency(),
            default => null,
        } ?? 'RUB';

        $currency = new DealCurrencyDTO($currencyCode, Currency::titleOf($currencyCode));
        $this->currencies[$currency->code] = $currency;
        return $currency;
    }

    /** @return  array<string, DealCurrencyDTO> */
    public function getCurrencies(): array
    {
        return $this->currencies;
    }
}
