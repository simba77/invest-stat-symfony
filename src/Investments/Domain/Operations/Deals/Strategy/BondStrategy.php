<?php

declare(strict_types=1);

namespace App\Investments\Domain\Operations\Deals\Strategy;

use App\Investments\Domain\Instruments\Bond;
use App\Investments\Domain\Instruments\Securities\SecurityTypeEnum;
use App\Investments\Domain\Operations\Deal;
use RuntimeException;

class BondStrategy implements DealStrategyInterface
{
    public function __construct(
        private readonly Deal $deal
    ) {
    }

    private function getBond(): Bond
    {
        return $this->deal->getBond() ?? throw new RuntimeException('Bond is null');
    }

    #[\Override]
    public function getName(): string
    {
        return $this->getBond()->getShortName() ?? $this->getBond()->getName();
    }

    #[\Override]
    public function getSecurityType(): SecurityTypeEnum
    {
        return SecurityTypeEnum::Bond;
    }

    #[\Override]
    public function getBuyPrice(): string
    {
        return bcdiv(bcmul($this->deal->getBuyPrice(), $this->getBond()->getLotSize(), 4), '100', 4);
    }

    #[\Override]
    public function getSellPrice(): string
    {
        return bcdiv(bcmul($this->deal->getSellPrice(), $this->getBond()->getLotSize(), 4), '100', 4);
    }

    #[\Override]
    public function getCurrentPrice(): string
    {
        return bcdiv(bcmul($this->getBond()->getPrice(), $this->getBond()->getLotSize(), 4), '100', 4);
    }

    #[\Override]
    public function getPrevPrice(): string
    {
        return bcdiv(bcmul($this->getBond()->getPrevPrice(), $this->getBond()->getLotSize(), 4), '100', 4);
    }

    #[\Override]
    public function getCommission(string $price, string $quantity): string
    {
        return bcmul('0.5', $quantity, 4);
    }

    #[\Override]
    public function getCurrency(): string
    {
        return $this->getBond()->getCurrency() ?? 'RUB';
    }
}
