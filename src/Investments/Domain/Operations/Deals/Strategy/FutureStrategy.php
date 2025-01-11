<?php

declare(strict_types=1);

namespace App\Investments\Domain\Operations\Deals\Strategy;

use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Instruments\FuturesMultipliers;
use App\Investments\Domain\Instruments\Securities\SecurityTypeEnum;

class FutureStrategy implements DealStrategyInterface
{
    public readonly FuturesMultipliers $futuresMultipliers;

    /**
     * @inheritDoc
     */
    public function __construct(
        private readonly array $deal,
        public Account $account
    ) {
        $this->futuresMultipliers = new FuturesMultipliers();
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
        return bcmul(bcmul($this->deal['deal']->getBuyPrice(), $this->getMultiplier(), 4), $this->deal['futureLotSize'], 4);
    }

    public function getSellPrice(): string
    {
        return bcmul(bcmul($this->deal['deal']->getSellPrice(), $this->getMultiplier(), 4), $this->deal['futureLotSize'], 4);
    }

    public function getCurrentPrice(): string
    {
        return bcmul(bcmul($this->deal['futurePrice'], $this->getMultiplier(), 4), $this->deal['futureLotSize'], 4);
    }

    public function getPrevPrice(): string
    {
        return bcmul(bcmul($this->deal['futurePrevPrice'], $this->getMultiplier(), 4), $this->deal['futureLotSize'], 4);
    }

    public function getCommission(string $price, string $quantity): string
    {
        return bcmul('5', $quantity, 4);
    }

    public function getCurrency(): string
    {
        return $this->deal['futureCurrency'] ?? 'RUB';
    }

    private function getMultiplier(): string
    {
        $multiplier = $this->futuresMultipliers->getMultiplierForTicker($this->deal['deal']->getTicker());
        if (! is_null($multiplier)) {
            return $multiplier;
        }
        return $this->deal['futureStepPrice'] ?? '1';
    }
}
