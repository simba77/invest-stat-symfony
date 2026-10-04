<?php

declare(strict_types=1);

namespace App\Investments\Domain\Operations\Deals\Strategy;

use App\Investments\Domain\Instruments\Future;
use App\Investments\Domain\Instruments\Securities\SecurityTypeEnum;
use App\Investments\Domain\Operations\Deal;
use RuntimeException;

class FutureStrategy implements DealStrategyInterface
{
    public function __construct(
        private readonly Deal $deal
    ) {
    }

    private function getFuture(): Future
    {
        return $this->deal->getFuture() ?? throw new RuntimeException('Future is null');
    }

    public function getName(): string
    {
        return $this->getFuture()->getShortName() ?? $this->getFuture()->getName();
    }

    public function getSecurityType(): SecurityTypeEnum
    {
        return SecurityTypeEnum::Future;
    }

    public function getBuyPrice(): string
    {
        return bcmul(bcmul($this->deal->getBuyPrice(), $this->getMultiplier(), 4), $this->getFuture()->getLotSize(), 4);
    }

    public function getSellPrice(): string
    {
        return bcmul(bcmul($this->deal->getSellPrice() ?? '0', $this->getMultiplier(), 4), $this->getFuture()->getLotSize() ?? '1', 4);
    }

    public function getCurrentPrice(): string
    {
        return bcmul(bcmul($this->deal->getFuture()->getPrice(), $this->getMultiplier(), 4), $this->deal->getFuture()->getLotSize(), 4);
    }

    public function getPrevPrice(): string
    {
        return bcmul(bcmul($this->deal->getFuture()->getPrevPrice(), $this->getMultiplier(), 4), $this->deal->getFuture()->getLotSize(), 4);
    }

    public function getCommission(string $price, string $quantity): string
    {
        return bcmul('5', $quantity, 4);
    }

    public function getCurrency(): string
    {
        return $this->deal->getFuture()->getCurrency();
    }

    /**
     * @return numeric-string
     */
    private function getMultiplier(): string
    {
        return $this->getFuture()->getPointValue();
    }

    public function getInstrumentId(): ?int
    {
        return $this->deal->getFuture()->getId();
    }

    public function getBondPercent(): ?string
    {
        return null;
    }

    public function getBondBuyPercent(): ?string
    {
        return null;
    }
}
