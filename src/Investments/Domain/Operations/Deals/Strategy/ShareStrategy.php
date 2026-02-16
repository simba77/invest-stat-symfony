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

    #[\Override]
    public function getName(): string
    {
        return $this->deal->getShare()?->getShortName() ?? $this->deal->getShare()?->getName() ?? '';
    }

    #[\Override]
    public function getSecurityType(): SecurityTypeEnum
    {
        return SecurityTypeEnum::Share;
    }

    #[\Override]
    public function getBuyPrice(): string
    {
        return $this->deal->getBuyPrice();
    }

    #[\Override]
    public function getSellPrice(): string
    {
        return $this->deal->getSellPrice() ?? '0';
    }

    #[\Override]
    public function getCurrentPrice(): string
    {
        return $this->deal->getShare()?->getPrice() ?? '0';
    }

    #[\Override]
    public function getPrevPrice(): string
    {
        return $this->deal->getShare()?->getPrevPrice() ?? '0';
    }

    #[\Override]
    /**
     * @param numeric-string $price
     * @param numeric-string $quantity
     * @return numeric-string
     */
    public function getCommission(string $price, string $quantity): string
    {
        return bcmul($price, bcdiv($this->deal->getAccount()->getCommission(), '100', 4), 4);
    }

    #[\Override]
    public function getCurrency(): string
    {
        return $this->deal->getShare()?->getCurrency() ?? 'RUB';
    }

    public function getInstrumentId(): ?int
    {
        return $this->deal->getShare()->getId();
    }
}
