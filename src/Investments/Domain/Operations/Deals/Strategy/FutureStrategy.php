<?php

declare(strict_types=1);

namespace App\Investments\Domain\Operations\Deals\Strategy;

use App\Investments\Domain\Instruments\FuturesMultipliers;
use App\Investments\Domain\Instruments\Securities\SecurityTypeEnum;
use App\Investments\Domain\Operations\Deal;

class FutureStrategy implements DealStrategyInterface
{
    public readonly FuturesMultipliers $futuresMultipliers;

    public function __construct(
        private readonly Deal $deal
    ) {
        $this->futuresMultipliers = new FuturesMultipliers();
    }

    #[\Override]
    public function getName(): string
    {
        return $this->deal->getFuture()->getShortName();
    }

    #[\Override]
    public function getSecurityType(): SecurityTypeEnum
    {
        return SecurityTypeEnum::Future;
    }

    #[\Override]
    public function getBuyPrice(): string
    {
        return bcmul(bcmul($this->deal->getBuyPrice(), $this->getMultiplier(), 4), $this->deal->getFuture()->getLotSize(), 4);
    }

    #[\Override]
    public function getSellPrice(): string
    {
        return bcmul(bcmul($this->deal->getSellPrice(), $this->getMultiplier(), 4), $this->deal->getFuture()->getLotSize(), 4);
    }

    #[\Override]
    public function getCurrentPrice(): string
    {
        return bcmul(bcmul($this->deal->getFuture()->getPrice(), $this->getMultiplier(), 4), $this->deal->getFuture()->getLotSize(), 4);
    }

    #[\Override]
    public function getPrevPrice(): string
    {
        return bcmul(bcmul($this->deal->getFuture()->getPrevPrice(), $this->getMultiplier(), 4), $this->deal->getFuture()->getLotSize(), 4);
    }

    #[\Override]
    public function getCommission(string $price, string $quantity): string
    {
        return bcmul('5', $quantity, 4);
    }

    #[\Override]
    public function getCurrency(): string
    {
        return $this->deal->getFuture()->getCurrency();
    }

    private function getMultiplier(): string
    {
        $multiplier = $this->futuresMultipliers->getMultiplierForTicker($this->deal->getTicker());
        if (! is_null($multiplier)) {
            return $multiplier;
        }
        return $this->deal->getFuture()->getStepPrice() ?? '1';
    }
}
