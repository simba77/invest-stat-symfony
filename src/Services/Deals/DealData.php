<?php

declare(strict_types=1);

namespace App\Services\Deals;

use App\Entity\Account;
use App\Entity\Deal;
use App\Services\MarketData\Currencies\CurrencyService;
use App\Services\MarketData\Securities\SecurityTypeEnum;

class DealData
{
    /** @param array{
     *     deal: Deal,
     *     shareName?: string,
     *     bondName?: string,
     *     futureName?: string,
     *     sharePrice?: string,
     *     bondPrice?: string,
     *     bondLotSize?: string,
     *     futurePrice?: string,
     *     futureStepPrice?: string,
     *     futureLotSize?: string,
     *     shareCurrency?: string,
     *     futureCurrency?: string,
     *     bondCurrency?: string,
     * } $deal
     */
    public function __construct(
        private readonly array $deal,
        private readonly Account $account,
        private readonly CurrencyService $currencyService,
    ) {
    }

    public function getId(): int
    {
        return $this->deal['deal']->getId();
    }

    public function getAccountId(): int
    {
        return $this->account->getId();
    }

    public function getName(): string
    {
        return $this->deal['shareName'] ?? $this->deal['bondName'] ?? $this->deal['futureName'] ?? '';
    }

    public function getTicker(): string
    {
        return $this->deal['deal']->getTicker();
    }

    public function getSecurityType(): SecurityTypeEnum
    {
        if ($this->deal['futureName']) {
            return SecurityTypeEnum::Future;
        }
        if ($this->deal['bondName']) {
            return SecurityTypeEnum::Bond;
        }
        return SecurityTypeEnum::Share;
    }

    public function getBuyPrice(): float
    {
        // Futures price
        if ($this->deal['futureName']) {
            return (float) ($this->deal['deal']->getBuyPrice() * $this->deal['futureStepPrice'] * $this->deal['futureLotSize']);
        }

        // Bond price
        if ($this->deal['bondName']) {
            return (float) ($this->deal['deal']->getBuyPrice() * $this->deal['bondLotSize'] / 100);
        }

        return (float) $this->deal['deal']->getBuyPrice();
    }

    public function getQuantity(): int
    {
        return (int) $this->deal['deal']->getQuantity();
    }

    public function getFullBuyPrice(): float
    {
        return $this->getBuyPrice() * $this->getQuantity();
    }

    public function getSellPrice(): float
    {
        // Futures price
        if ($this->deal['futureName']) {
            return (float) ($this->deal['deal']->getSellPrice() * $this->deal['futureStepPrice'] * $this->deal['futureLotSize']);
        }

        // Bond price
        if ($this->deal['bondName']) {
            return (float) ($this->deal['deal']->getSellPrice() * $this->deal['bondLotSize'] / 100);
        }

        return (float) $this->deal['deal']->getSellPrice();
    }

    public function getFullSellPrice(): float
    {
        return $this->getSellPrice() * $this->getQuantity();
    }

    public function getCurrentPrice(): float
    {
        if ($this->deal['futurePrice']) {
            return (float) ($this->deal['futurePrice'] * $this->deal['futureStepPrice'] * $this->deal['futureLotSize']);
        }

        if ($this->deal['bondPrice']) {
            return (float) ($this->deal['bondPrice'] * $this->deal['bondLotSize'] / 100);
        }

        return (float) $this->deal['sharePrice'] ?? $this->deal['bondPrice'] ?? 0;
    }

    public function getFullCurrentPrice(): float
    {
        return $this->getCurrentPrice() * $this->getQuantity();
    }

    public function getTargetPrice(): float
    {
        return (float) $this->deal['deal']->getTargetPrice();
    }

    public function getFullTargetPrice(): float
    {
        return $this->getTargetPrice() * $this->getQuantity();
    }

    public function getCommission(): float | int
    {
        if (! empty($this->deal['futurePrice'])) {
            // TODO: Change commission
            return 5 * $this->getQuantity();
        }
        if (! empty($this->deal['bondPrice'])) {
            // TODO: Change commission
            return 0.5 * $this->getQuantity();
        }
        return round($this->getFullCurrentPrice() * ($this->account->getCommission() / 100), 2);
    }

    public function getProfit(): float
    {
        if ($this->deal['deal']->getStatus() === DealStatus::Closed) {
            if ($this->deal['deal']->getType() === DealType::Short) {
                return round($this->getFullBuyPrice() - $this->getFullSellPrice() - $this->getCommission(), 2);
            }
            return round($this->getFullSellPrice() - $this->getFullBuyPrice() - $this->getCommission(), 2);
        }

        if ($this->deal['deal']->getType() === DealType::Short) {
            return round($this->getFullBuyPrice() - $this->getFullCurrentPrice() - $this->getCommission(), 2);
        }
        return round($this->getFullCurrentPrice() - $this->getFullBuyPrice() - $this->getCommission(), 2);
    }

    public function getProfitPercent(): float
    {
        return round($this->getProfit() / $this->getFullBuyPrice() * 100, 2);
    }

    public function getTargetProfit(): float | int
    {
        if (! $this->getTargetPrice()) {
            return 0;
        }

        if ($this->deal['deal']->getType() === DealType::Short) {
            return round($this->getBuyPrice() - $this->getTargetPrice(), 2);
        }
        return round($this->getTargetPrice() - $this->getBuyPrice(), 2);
    }

    public function getFullTargetProfit(): float | int
    {
        if (! $this->getTargetPrice()) {
            return 0;
        }

        if ($this->deal['deal']->getType() === DealType::Short) {
            return round($this->getFullBuyPrice() - $this->getFullTargetPrice(), 2);
        }
        return round($this->getFullTargetPrice() - $this->getFullBuyPrice(), 2);
    }

    public function getTargetProfitPercent(): float
    {
        if (! $this->getFullTargetPrice()) {
            return 0;
        }
        return round($this->getFullTargetProfit() / $this->getFullBuyPrice() * 100, 2);
    }

    public function getCurrency(): string
    {
        return $this->deal['shareCurrency'] ?? $this->deal['bondCurrency'] ?? $this->deal['futureCurrency'] ?? 'RUB';
    }

    public function getCurrencyName(): string
    {
        $currency = $this->deal['shareCurrency'] ?? $this->deal['bondCurrency'] ?? $this->deal['futureCurrency'] ?? 'RUB';

        if ($currency === 'RUB') {
            return '₽';
        }
        return '$';
    }

    public function getType(): ?DealType
    {
        return $this->deal['deal']->getType();
    }

    public function getStatus(): DealStatus
    {
        return $this->deal['deal']->getStatus();
    }

    public function getCreatedAt(): string
    {
        return $this->deal['deal']->createdAt()->format('d.m.Y H:i');
    }

    public function getUpdatedAt(): ?string
    {
        return $this->deal['deal']->updatedAt()?->format('d.m.Y H:i');
    }

    public function getBuyPriceInBaseCurrency(): float
    {
        if ($this->getCurrency() === 'RUB') {
            return $this->getBuyPrice();
        }
        return $this->getBuyPrice() * $this->currencyService->getUSDRUBRate();
    }

    public function getSellPriceInBaseCurrency(): float
    {
        if ($this->getCurrency() === 'RUB') {
            return $this->getSellPrice();
        }
        return $this->getSellPrice() * $this->currencyService->getUSDRUBRate();
    }

    public function getFullBuyPriceInBaseCurrency(): float
    {
        return $this->getBuyPriceInBaseCurrency() * $this->getQuantity();
    }

    public function getFullSellPriceInBaseCurrency(): float
    {
        return $this->getSellPriceInBaseCurrency() * $this->getQuantity();
    }

    public function getCurrentPriceInBaseCurrency(): float
    {
        if ($this->getCurrency() === 'RUB') {
            return $this->getCurrentPrice();
        }
        return $this->getCurrentPrice() * $this->currencyService->getUSDRUBRate();
    }

    public function getFullCurrentPriceInBaseCurrency(): float
    {
        return $this->getCurrentPriceInBaseCurrency() * $this->getQuantity();
    }

    public function getProfitInBaseCurrency(): float | int
    {
        if ($this->getCurrency() === 'RUB') {
            return $this->getProfit();
        }
        return $this->getProfit() * $this->currencyService->getUSDRUBRate();
    }
}
