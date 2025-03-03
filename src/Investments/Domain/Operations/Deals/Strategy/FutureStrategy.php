<?php

declare(strict_types=1);

namespace App\Investments\Domain\Operations\Deals\Strategy;

use App\Investments\Domain\Instruments\Future;
use App\Investments\Domain\Instruments\FuturesMultipliers;
use App\Investments\Domain\Instruments\Securities\SecurityTypeEnum;
use App\Investments\Domain\Operations\Deal;
use RuntimeException;

class FutureStrategy implements DealStrategyInterface
{
    public readonly FuturesMultipliers $futuresMultipliers;

    public function __construct(
        private readonly Deal $deal
    ) {
        $this->futuresMultipliers = new FuturesMultipliers();
    }

    private function getFuture(): Future
    {
        return $this->deal->getFuture() ?? throw new RuntimeException('Future is null');
    }

    #[\Override]
    public function getName(): string
    {
        return $this->getFuture()->getShortName() ?? $this->getFuture()->getName();
    }

    #[\Override]
    public function getSecurityType(): SecurityTypeEnum
    {
        return SecurityTypeEnum::Future;
    }

    #[\Override]
    public function getBuyPrice(): string
    {
        return bcmul(bcmul($this->deal->getBuyPrice(), $this->getMultiplier(), 4), $this->getFuture()->getLotSize(), 4);
    }

    #[\Override]
    public function getSellPrice(): string
    {
        return bcmul(bcmul($this->deal->getSellPrice() ?? '0', $this->getMultiplier(), 4), $this->getFuture()->getLotSize() ?? '1', 4);
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

    /**
     * @return numeric-string
     */
    private function getMultiplier(): string
    {
        $multiplier = $this->futuresMultipliers->getMultiplierForTicker($this->deal->getTicker());
        if (! is_null($multiplier)) {
            return $multiplier;
        }
        return $this->deal->getFuture()?->getStepPrice() ?? '1';
    }
}
