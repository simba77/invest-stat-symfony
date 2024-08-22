<?php

declare(strict_types=1);

namespace App\Services\Deals\Strategy;

use App\Entity\Account;
use App\Services\MarketData\Securities\SecurityTypeEnum;

class BondStrategy implements DealStrategyInterface
{
    public function __construct(private readonly array $deal, Account $account)
    {
    }

    public function getName(): string
    {
        return $this->deal['bondName'];
    }

    public function getSecurityType(): SecurityTypeEnum
    {
        return SecurityTypeEnum::Bond;
    }

    public function getBuyPrice(): string
    {
        return bcdiv(bcmul($this->deal['deal']->getBuyPrice(), $this->deal['bondLotSize'], 4), '100', 4);
    }

    public function getSellPrice(): string
    {
        return bcdiv(bcmul($this->deal['deal']->getSellPrice(), $this->deal['bondLotSize'], 4), '100', 4);
    }

    public function getCurrentPrice(): string
    {
        return bcdiv(bcmul($this->deal['bondPrice'], $this->deal['bondLotSize'], 4), '100', 4);
    }

    public function getPrevPrice(): string
    {
        return bcdiv(bcmul($this->deal['bondPrevPrice'], $this->deal['bondLotSize'], 4), '100', 4);
    }

    public function getCommission(string $price, string $quantity): string
    {
        return bcmul('0.5', $quantity, 4);
    }

    public function getCurrency(): string
    {
        return $this->deal['bondCurrency'] ?? 'RUB';
    }
}
