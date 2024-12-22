<?php

declare(strict_types=1);

namespace App\Domain\Investments\Operations\Deals\Strategy;

use App\Domain\Investments\Accounts\Account;
use App\Domain\Investments\Instruments\Securities\SecurityTypeEnum;

class ShareStrategy implements DealStrategyInterface
{
    public function __construct(private readonly array $deal, private readonly Account $account)
    {
    }

    public function getName(): string
    {
        return $this->deal['shareName'] ?? '';
    }

    public function getSecurityType(): SecurityTypeEnum
    {
        return SecurityTypeEnum::Share;
    }

    public function getBuyPrice(): string
    {
        return $this->deal['deal']->getBuyPrice();
    }

    public function getSellPrice(): string
    {
        return $this->deal['deal']->getSellPrice();
    }

    public function getCurrentPrice(): string
    {
        return $this->deal['sharePrice'] ?? $this->deal['bondPrice'] ?? '0';
    }

    public function getPrevPrice(): string
    {
        return $this->deal['sharePrevPrice'] ?? $this->deal['bondPrevPrice'] ?? '0';
    }

    public function getCommission(string $price, string $quantity): string
    {
        return bcmul($price, bcdiv($this->account->getCommission(), '100', 4), 4);
    }

    public function getCurrency(): string
    {
        return $this->deal['shareCurrency'] ?? 'RUB';
    }
}
