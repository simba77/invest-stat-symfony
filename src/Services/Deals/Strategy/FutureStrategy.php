<?php

declare(strict_types=1);

namespace App\Services\Deals\Strategy;

use App\Entity\Account;
use App\Services\MarketData\Securities\SecurityTypeEnum;

class FutureStrategy implements DealStrategyInterface
{
    /**
     * @inheritDoc
     */
    public function __construct(private readonly array $deal, Account $account)
    {
    }

    public function getName(): string
    {
        return $this->deal['futureName'];
    }

    public function getSecurityType(): SecurityTypeEnum
    {
        return SecurityTypeEnum::Future;
    }

    public function getBuyPrice(): string
    {
        if ($this->deal['deal']->getBuyPrice() > $this->deal['futureLotSize']) {
            return bcmul($this->deal['deal']->getBuyPrice(), $this->deal['futureStepPrice'], 4);
        }

        return bcmul(bcmul($this->deal['deal']->getBuyPrice(), $this->deal['futureStepPrice'], 4), $this->deal['futureLotSize'], 4);
    }

    public function getSellPrice(): string
    {
        if ($this->deal['deal']->getSellPrice() > $this->deal['futureLotSize']) {
            return bcmul($this->deal['deal']->getSellPrice(), $this->deal['futureStepPrice'], 4);
        }
        return bcmul(bcmul($this->deal['deal']->getSellPrice(), $this->deal['futureStepPrice'], 4), $this->deal['futureLotSize'], 4);
    }

    public function getCurrentPrice(): string
    {
        if ($this->deal['futurePrice'] > $this->deal['futureLotSize']) {
            return bcmul($this->deal['futurePrice'], $this->deal['futureStepPrice'], 4);
        }
        return bcmul(bcmul($this->deal['futurePrice'], $this->deal['futureStepPrice'], 4), $this->deal['futureLotSize'], 4);
    }

    public function getPrevPrice(): string
    {
        if ($this->deal['futurePrevPrice'] > $this->deal['futureLotSize']) {
            return bcmul($this->deal['futurePrevPrice'], $this->deal['futureStepPrice'], 4);
        }

        return bcmul(bcmul($this->deal['futurePrevPrice'], $this->deal['futureStepPrice'], 4), $this->deal['futureLotSize'], 4);
    }

    public function getCommission(string $price, string $quantity): string
    {
        return bcmul('5', $quantity, 4);
    }

    public function getCurrency(): string
    {
        return $this->deal['futureCurrency'] ?? 'RUB';
    }
}
