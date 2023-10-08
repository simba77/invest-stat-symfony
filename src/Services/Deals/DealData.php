<?php

declare(strict_types=1);

namespace App\Services\Deals;

use App\Entity\Account;
use App\Entity\Deal;

class DealData
{
    /** @param array{
     *     deal: Deal,
     *     shareName?: string,
     *     bondName?: string,
     *     futureName?: string,
     *     sharePrice?: string,
     *     bondPrice?: string,
     *     futurePrice?: string,
     * } $deal
     */
    public function __construct(
        private readonly array $deal,
        private readonly Account $account,
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

    public function getBuyPrice(): float
    {
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

    public function getCurrentPrice(): float
    {
        return (float) $this->deal['sharePrice'] ?? $this->deal['bondPrice'] ?? $this->deal['futurePrice'] ?? 0;
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

    public function getCurrencyName(): string
    {
        $currency = $deal['shareCurrency'] ?? $deal['bondCurrency'] ?? $deal['futureCurrency'] ?? 'RUB';

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
}
