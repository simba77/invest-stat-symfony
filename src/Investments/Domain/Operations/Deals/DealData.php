<?php

declare(strict_types=1);

namespace App\Investments\Domain\Operations\Deals;

use App\Investments\Domain\Instruments\Currencies\Currency;
use App\Investments\Domain\Instruments\Currencies\CurrencyService;
use App\Investments\Domain\Instruments\Securities\SecurityTypeEnum;
use App\Investments\Domain\Operations\Deal;
use App\Investments\Domain\Operations\Deals\Strategy\DealStrategyFactory;
use App\Investments\Domain\Operations\Deals\Strategy\DealStrategyInterface;

class DealData
{
    private DealStrategyInterface $strategy;

    public function __construct(
        private readonly Deal $deal,
        private readonly CurrencyService $currencyService,
    ) {
        $this->strategy = DealStrategyFactory::create($this->deal);
    }

    public function getId(): int
    {
        return $this->deal->getId() ?? throw new \RuntimeException('Deal id is not set');
    }

    public function getAccountId(): int
    {
        return $this->deal->getAccount()->getId() ?? throw new \RuntimeException('Account is not set');
    }

    public function getAccountName(): string
    {
        return $this->deal->getAccount()->getName() ?? throw new \RuntimeException('Account name not set');
    }

    public function getName(): string
    {
        return $this->strategy->getName();
    }

    public function getTicker(): string
    {
        return $this->deal->getTicker();
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
        return $this->deal->getQuantity();
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
        return bcmul($this->getFullDailyProfit(), $this->currencyService->getCurrencyRate($this->getCurrency()), 4);
    }

    public function getTargetPrice(): string
    {
        return $this->deal->getTargetPrice() ?? '0';
    }

    public function getFullTargetPrice(): string
    {
        return bcmul($this->getTargetPrice(), (string) $this->getQuantity(), 4);
    }

    public function getCommission(): string
    {
        // A synced deal knows what the broker charged; a manual one estimates by the account rate
        $buyCommission = $this->deal->getBuyCommission();
        if ($buyCommission !== null) {
            return bcadd($buyCommission, $this->deal->getSellCommission() ?? '0', 4);
        }

        return $this->strategy->getCommission($this->getFullCurrentPrice(), (string) $this->getQuantity());
    }

    public function getProfit(): string
    {
        if ($this->deal->getStatus() === DealStatus::Closed) {
            if ($this->deal->getType() === DealType::Short) {
                return bcsub(bcsub($this->getFullBuyPrice(), $this->getFullSellPrice(), 4), $this->getCommission(), 4);
            }
            return bcsub(bcsub($this->getFullSellPrice(), $this->getFullBuyPrice(), 4), $this->getCommission(), 4);
        }

        if ($this->deal->getType() === DealType::Short) {
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

        if ($this->deal->getType() === DealType::Short) {
            return bcsub($this->getBuyPrice(), $this->getTargetPrice(), 4);
        }
        return bcsub($this->getTargetPrice(), $this->getBuyPrice(), 4);
    }

    public function getFullTargetProfit(): string
    {
        if (! $this->getTargetPrice()) {
            return '0';
        }

        if ($this->deal->getType() === DealType::Short) {
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
        return Currency::symbolOf($this->getCurrency());
    }

    public function getType(): ?DealType
    {
        return $this->deal->getType();
    }

    public function getStatus(): DealStatus
    {
        return $this->deal->getStatus();
    }

    public function getCreatedAt(): string
    {
        return $this->deal->createdAt()->format('d.m.Y H:i');
    }

    public function getUpdatedAt(): ?string
    {
        return $this->deal->updatedAt()?->format('d.m.Y H:i');
    }

    public function getClosingDate(): ?string
    {
        return $this->deal->getClosingDate()?->format('d.m.Y H:i');
    }

    /**
     * At the rate of the day the deal was opened: that is what it cost in roubles.
     */
    public function getBuyPriceInBaseCurrency(): string
    {
        if ($this->getCurrency() === 'RUB') {
            return $this->getBuyPrice();
        }
        return bcmul($this->getBuyPrice(), $this->rateWhenOpened(), 4);
    }

    /**
     * At the rate of the day the deal was closed: that is what it brought in roubles.
     */
    public function getSellPriceInBaseCurrency(): string
    {
        if ($this->getCurrency() === 'RUB') {
            return $this->getSellPrice();
        }
        return bcmul($this->getSellPrice(), $this->rateWhenClosed(), 4);
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
        return bcmul($this->getCurrentPrice(), $this->currencyService->getCurrencyRate($this->getCurrency()), 4);
    }

    public function getFullCurrentPriceInBaseCurrency(): string
    {
        return bcmul($this->getCurrentPriceInBaseCurrency(), (string) $this->getQuantity(), 4);
    }

    /**
     * The result in roubles, as the tax counts it: what the deal brought or is worth now at the rate
     * of that day, less what it cost at the rate of the day it was opened. So it includes the change
     * of the currency rate, and a deal can earn roubles while losing dollars.
     */
    public function getProfitInBaseCurrency(): string
    {
        if ($this->getCurrency() === 'RUB') {
            return $this->getProfit();
        }
        // The price of a future is not money paid: its result is converted as a whole
        if ($this->getSecurityType() === SecurityTypeEnum::Future) {
            return bcmul($this->getProfit(), $this->rateWhenClosed(), 4);
        }

        $cost = $this->getFullBuyPriceInBaseCurrency();
        $value = $this->deal->getStatus() === DealStatus::Closed
            ? $this->getFullSellPriceInBaseCurrency()
            : $this->getFullCurrentPriceInBaseCurrency();
        $result = $this->deal->getType() === DealType::Short ? bcsub($cost, $value, 4) : bcsub($value, $cost, 4);

        return bcsub($result, $this->getCommissionInBaseCurrency(), 4);
    }

    /**
     * Each commission at the rate of the day it was charged; an estimated one when the deal is closed.
     */
    private function getCommissionInBaseCurrency(): string
    {
        $buyCommission = $this->deal->getBuyCommission();
        if ($buyCommission !== null) {
            return bcadd(
                bcmul($buyCommission, $this->rateWhenOpened(), 4),
                bcmul($this->deal->getSellCommission() ?? '0', $this->rateWhenClosed(), 4),
                4,
            );
        }

        return bcmul($this->getCommission(), $this->rateWhenClosed(), 4);
    }

    /**
     * @return numeric-string
     */
    private function rateWhenOpened(): string
    {
        return $this->currencyService->getCurrencyRateOn($this->getCurrency(), $this->deal->createdAt());
    }

    /**
     * The rate of the closing day, or the current one while the deal is open.
     */
    private function rateWhenClosed(): string
    {
        $closedAt = $this->deal->getStatus() === DealStatus::Closed ? $this->deal->getClosingDate() : null;

        return $closedAt !== null
            ? $this->currencyService->getCurrencyRateOn($this->getCurrency(), $closedAt)
            : $this->currencyService->getCurrencyRate($this->getCurrency());
    }

    public function getInstrumentType(): string
    {
        return $this->strategy->getSecurityType()->getCode();
    }

    public function getInstrumentId(): ?int
    {
        return $this->strategy->getInstrumentId();
    }

    public function getBondPercent(): ?string
    {
        return $this->strategy->getBondPercent();
    }

    public function getBondBuyPercent(): ?string
    {
        return $this->strategy->getBondBuyPercent();
    }
}
