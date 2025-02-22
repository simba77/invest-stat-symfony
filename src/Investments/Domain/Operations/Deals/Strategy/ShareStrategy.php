<?php

declare(strict_types=1);

namespace App\Investments\Domain\Operations\Deals\Strategy;

use App\Investments\Domain\Instruments\Securities\SecurityTypeEnum;
use App\Investments\Domain\Operations\Deal;

class ShareStrategy implements DealStrategyInterface
{
    public function __construct(
        private readonly Deal $deal
    ) {
    }

    public function getName(): string
    {
        return $this->deal->getShare()?->getShortName() ?? '';
    }

    public function getSecurityType(): SecurityTypeEnum
    {
        return SecurityTypeEnum::Share;
    }

    public function getBuyPrice(): string
    {
        return $this->deal->getBuyPrice();
    }

    public function getSellPrice(): string
    {
        return $this->deal->getSellPrice();
    }

    public function getCurrentPrice(): string
    {
        return $this->deal->getShare()?->getPrice() ?? '0';
    }

    public function getPrevPrice(): string
    {
        return $this->deal->getShare()->getPrevPrice() ?? '0';
    }

    public function getCommission(string $price, string $quantity): string
    {
        return bcmul($price, bcdiv($this->deal->getAccount()->getCommission(), '100', 4), 4);
    }

    public function getCurrency(): string
    {
        return $this->deal->getShare()?->getCurrency() ?? 'RUB';
    }
}
