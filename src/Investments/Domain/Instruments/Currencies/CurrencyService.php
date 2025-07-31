<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments\Currencies;

use App\Investments\Infrastructure\Persistence\Repository\CurrencyRateRepository;

class CurrencyService
{
    public function __construct(
        private readonly CurrencyRateRepository $currencyRateRepository,
        private array $rates = []
    ) {
    }

    /**
     * Get RUB per USD rate
     */
    public function getUSDRUBRate(): string
    {
        return $this->getCurrencyRate('USD');
    }

    public function getCurrencyRate(string $targetCurrency = 'USD'): string
    {
        if (isset($this->rates[$targetCurrency])) {
            return $this->rates[$targetCurrency];
        }
        $currencyRate = $this->currencyRateRepository->findOneBy(['baseCurrency' => 'RUB', 'targetCurrency' => $targetCurrency]);
        $this->rates[$targetCurrency] = $currencyRate?->getRate() ?? '0';
        return $this->rates[$targetCurrency];
    }
}
