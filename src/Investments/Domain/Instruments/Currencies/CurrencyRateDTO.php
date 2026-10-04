<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments\Currencies;

class CurrencyRateDTO implements CurrencyRateInterface
{
    public function __construct(
        public string $baseCurrency,
        public string $targetCurrency,
        public string $rate,
        public \DateTimeImmutable $date,
    ) {
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getBaseCurrency(): string
    {
        return $this->baseCurrency;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getTargetCurrency(): string
    {
        return $this->targetCurrency;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getRate(): string
    {
        return $this->rate;
    }

    #[\Override]
    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }
}
