<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments\Currencies;

use App\Investments\Infrastructure\Persistence\Repository\CurrencyRateRepository;

class CurrencyService
{
    public function __construct(
        private readonly CurrencyRateRepository $currencyRateRepository,
        private ?string $usdRubRate = null
    ) {
    }

    /**
     * Get RUB per USD rate
     */
    public function getUSDRUBRate(): string
    {
        if ($this->usdRubRate !== null) {
            return $this->usdRubRate;
        }
        $currencyRate = $this->currencyRateRepository->findOneBy(['baseCurrency' => 'RUB', 'targetCurrency' => 'USD']);
        $this->usdRubRate = $currencyRate?->getRate() ?? '0';
        return $this->usdRubRate;
    }
}
