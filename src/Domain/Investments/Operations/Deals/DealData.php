<?php

declare(strict_types=1);

namespace App\Domain\Investments\Operations\Deals;

use App\Domain\Investments\Accounts\Account;
use App\Domain\Investments\Instruments\Currencies\CurrencyService;
use App\Domain\Investments\Instruments\Securities\SecurityTypeEnum;
use App\Domain\Investments\Operations\Deal;
use App\Domain\Investments\Operations\Deals\Strategy\BondStrategy;
use App\Domain\Investments\Operations\Deals\Strategy\DealStrategyInterface;
use App\Domain\Investments\Operations\Deals\Strategy\FutureStrategy;
use App\Domain\Investments\Operations\Deals\Strategy\ShareStrategy;

class DealData
{
    private DealStrategyInterface $strategy;

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
        if ($this->deal['futureName']) {
            $this->setStrategy(new FutureStrategy($this->deal, $this->account));
        } elseif ($this->deal['bondName']) {
            $this->setStrategy(new BondStrategy($this->deal, $this->account));
        } else {
            $this->setStrategy(new ShareStrategy($this->deal, $this->account));
        }
    }

    public function setStrategy(DealStrategyInterface $strategy): void
    {
        $this->strategy = $strategy;
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
        return $this->strategy->getName();
    }

    public function getTicker(): string
    {
        return $this->deal['deal']->getTicker();
    }

    public function getSecurityType(): SecurityTypeEnum
    {
        return $this->strategy->getSecurityType();
    }

    public function getBuyPrice(): string
    {
        return $this->strategy->getBuyPrice();
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
        return $this->strategy->getSellPrice();
    }

    public function getFullSellPrice(): string
    {
        return bcmul($this->getSellPrice(), (string) $this->getQuantity(), 4);
    }

    public function getCurrentPrice(): string
    {
        return $this->strategy->getCurrentPrice();
    }

    public function getPrevPrice(): string
    {
        return $this->strategy->getPrevPrice();
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
        return $this->strategy->getCommission($this->getFullCurrentPrice(), (string) $this->getQuantity());
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
        return $this->strategy->getCurrency();
    }

    public function getCurrencyName(): string
    {
        if ($this->getCurrency() === 'RUB') {
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
