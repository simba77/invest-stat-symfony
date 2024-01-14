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
     *     sharePrevPrice?: string,
     *     bondPrice?: string,
     *     bondPrevPrice?: string,
     *     bondLotSize?: string,
     *     futurePrice?: string,
     *     futurePrevPrice?: string,
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

    public function getBuyPrice(): string
    {
        // Futures price
        if ($this->deal['futureName']) {
            return bcmul(bcmul($this->deal['deal']->getBuyPrice(), $this->deal['futureStepPrice'], 4), $this->deal['futureLotSize'], 4);
        }

        // Bond price
        if ($this->deal['bondName']) {
            return bcdiv(bcmul($this->deal['deal']->getBuyPrice(), $this->deal['bondLotSize'], 4), '100', 4);
        }

        return $this->deal['deal']->getBuyPrice();
    }

    public function getQuantity(): int
    {
        return (int) $this->deal['deal']->getQuantity();
    }

    public function getFullBuyPrice(): string
    {
        return bcmul($this->getBuyPrice(), (string) $this->getQuantity(), 4);
    }

    public function getSellPrice(): string
    {
        // Futures price
        if ($this->deal['futureName']) {
            return bcmul(bcmul($this->deal['deal']->getSellPrice(), $this->deal['futureStepPrice'], 4), $this->deal['futureLotSize'], 4);
        }

        // Bond price
        if ($this->deal['bondName']) {
            return bcdiv(bcmul($this->deal['deal']->getSellPrice(), $this->deal['bondLotSize'], 4), '100', 4);
        }

        return $this->deal['deal']->getSellPrice();
    }

    public function getFullSellPrice(): string
    {
        return bcmul($this->getSellPrice(), (string) $this->getQuantity(), 4);
    }

    public function getCurrentPrice(): string
    {
        if ($this->deal['futurePrice']) {
            return bcmul(bcmul($this->deal['futurePrice'], $this->deal['futureStepPrice'], 4), $this->deal['futureLotSize'], 4);
        }

        if ($this->deal['bondPrice']) {
            return bcdiv(bcmul($this->deal['bondPrice'], $this->deal['bondLotSize'], 4), '100', 4);
        }

        return $this->deal['sharePrice'] ?? $this->deal['bondPrice'] ?? '0';
    }

    public function getPrevPrice(): string
    {
        if ($this->deal['futurePrevPrice']) {
            return bcmul(bcmul($this->deal['futurePrevPrice'], $this->deal['futureStepPrice'], 4), $this->deal['futureLotSize'], 4);
        }

        if ($this->deal['bondPrevPrice']) {
            return bcdiv(bcmul($this->deal['bondPrevPrice'], $this->deal['bondLotSize'], 4), '100', 4);
        }

        return $this->deal['sharePrevPrice'] ?? $this->deal['bondPrevPrice'] ?? '0';
    }

    public function getFullCurrentPrice(): string
    {
        return bcmul($this->getCurrentPrice(), (string) $this->getQuantity(), 4);
    }

    public function getFullPrevPrice(): string
    {
        return bcmul($this->getPrevPrice(), (string) $this->getQuantity(), 4);
    }

    public function getDailyProfit(): string
    {
        return bcsub($this->getCurrentPrice(), $this->getPrevPrice(), 4);
    }

    public function getFullDailyProfit(): string
    {
        return bcsub($this->getFullCurrentPrice(), $this->getFullPrevPrice(), 4);
    }

    public function getFullDailyProfitInBaseCurrency(): string
    {
        if ($this->getCurrency() === 'RUB') {
            return $this->getFullDailyProfit();
        }
        return bcmul($this->getFullDailyProfit(), $this->currencyService->getUSDRUBRate(), 4);
    }

    public function getTargetPrice(): string
    {
        return $this->deal['deal']->getTargetPrice();
    }

    public function getFullTargetPrice(): string
    {
        return bcmul($this->getTargetPrice(), (string) $this->getQuantity(), 4);
    }

    public function getCommission(): string
    {
        if (! empty($this->deal['futurePrice'])) {
            // TODO: Change commission
            return bcmul('5', (string) $this->getQuantity(), 4);
        }
        if (! empty($this->deal['bondPrice'])) {
            // TODO: Change commission
            return bcmul('0.5', (string) $this->getQuantity(), 4);
        }

        return bcmul($this->getFullCurrentPrice(), bcdiv($this->account->getCommission(), '100', 4), 4);
    }

    public function getProfit(): string
    {
        if ($this->deal['deal']->getStatus() === DealStatus::Closed) {
            if ($this->deal['deal']->getType() === DealType::Short) {
                return bcsub(bcsub($this->getFullBuyPrice(), $this->getFullSellPrice(), 4), $this->getCommission(), 4);
            }
            return bcsub(bcsub($this->getFullSellPrice(), $this->getFullBuyPrice(), 4), $this->getCommission(), 4);
        }

        if ($this->deal['deal']->getType() === DealType::Short) {
            return bcsub(bcsub($this->getFullBuyPrice(), $this->getFullCurrentPrice(), 4), $this->getCommission(), 4);
        }
        return bcsub(bcsub($this->getFullCurrentPrice(), $this->getFullBuyPrice(), 4), $this->getCommission(), 4);
    }

    public function getProfitPercent(): string
    {
        return bcmul(bcdiv($this->getProfit(), $this->getFullBuyPrice(), 4), '100', 4);
    }

    public function getTargetProfit(): string
    {
        if (! $this->getTargetPrice()) {
            return '0';
        }

        if ($this->deal['deal']->getType() === DealType::Short) {
            return bcsub($this->getBuyPrice(), $this->getTargetPrice(), 4);
        }
        return bcsub($this->getTargetPrice(), $this->getBuyPrice(), 4);
    }

    public function getFullTargetProfit(): string
    {
        if (! $this->getTargetPrice()) {
            return '0';
        }

        if ($this->deal['deal']->getType() === DealType::Short) {
            return bcsub($this->getFullBuyPrice(), $this->getFullTargetPrice(), 4);
        }
        return bcsub($this->getFullTargetPrice(), $this->getFullBuyPrice(), 4);
    }

    public function getTargetProfitPercent(): string
    {
        if (! $this->getFullTargetPrice()) {
            return '0';
        }
        return bcmul(bcdiv($this->getFullTargetProfit(), $this->getFullBuyPrice(), 4), '100', 4);
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

    public function getClosingDate(): ?string
    {
        return $this->deal['deal']->getClosingDate()?->format('d.m.Y H:i');
    }

    public function getBuyPriceInBaseCurrency(): string
    {
        if ($this->getCurrency() === 'RUB') {
            return $this->getBuyPrice();
        }
        return bcmul($this->getBuyPrice(), $this->currencyService->getUSDRUBRate(), 4);
    }

    public function getSellPriceInBaseCurrency(): string
    {
        if ($this->getCurrency() === 'RUB') {
            return $this->getSellPrice();
        }
        return bcmul($this->getSellPrice(), $this->currencyService->getUSDRUBRate(), 4);
    }

    public function getFullBuyPriceInBaseCurrency(): string
    {
        return bcmul($this->getBuyPriceInBaseCurrency(), (string) $this->getQuantity(), 4);
    }

    public function getFullSellPriceInBaseCurrency(): string
    {
        return bcmul($this->getSellPriceInBaseCurrency(), (string) $this->getQuantity(), 4);
    }

    public function getCurrentPriceInBaseCurrency(): string
    {
        if ($this->getCurrency() === 'RUB') {
            return $this->getCurrentPrice();
        }
        return bcmul($this->getCurrentPrice(), $this->currencyService->getUSDRUBRate(), 4);
    }

    public function getFullCurrentPriceInBaseCurrency(): string
    {
        return bcmul($this->getCurrentPriceInBaseCurrency(), (string) $this->getQuantity(), 4);
    }

    public function getProfitInBaseCurrency(): string
    {
        if ($this->getCurrency() === 'RUB') {
            return $this->getProfit();
        }
        return bcmul($this->getProfit(), $this->currencyService->getUSDRUBRate(), 4);
    }
}
