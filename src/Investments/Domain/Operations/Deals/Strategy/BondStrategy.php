<?php

declare(strict_types=1);

namespace App\Investments\Domain\Operations\Deals\Strategy;

use App\Investments\Domain\Instruments\Securities\SecurityTypeEnum;
use App\Investments\Domain\Operations\Deal;

class BondStrategy implements DealStrategyInterface
{
    public function __construct(
        private readonly Deal $deal
    ) {
    }

    #[\Override]
    public function getName(): string
    {
        return $this->deal->getBond()->getShortName();
    }

    #[\Override]
    public function getSecurityType(): SecurityTypeEnum
    {
        return SecurityTypeEnum::Bond;
    }

    #[\Override]
    public function getBuyPrice(): string
    {
        return bcdiv(bcmul($this->deal->getBuyPrice(), $this->deal->getBond()->getLotSize(), 4), '100', 4);
    }

    #[\Override]
    public function getSellPrice(): string
    {
        return bcdiv(bcmul($this->deal->getSellPrice(), $this->deal->getBond()->getLotSize(), 4), '100', 4);
    }

    #[\Override]
    public function getCurrentPrice(): string
    {
        return bcdiv(bcmul($this->deal->getBond()->getPrice(), $this->deal->getBond()->getLotSize(), 4), '100', 4);
    }

    #[\Override]
    public function getPrevPrice(): string
    {
        return bcdiv(bcmul($this->deal->getBond()->getPrevPrice(), $this->deal->getBond()->getLotSize(), 4), '100', 4);
    }

    #[\Override]
    public function getCommission(string $price, string $quantity): string
    {
        return bcmul('0.5', $quantity, 4);
    }

    #[\Override]
    public function getCurrency(): string
    {
        return $this->deal->getBond()->getCurrency() ?? 'RUB';
    }
}
