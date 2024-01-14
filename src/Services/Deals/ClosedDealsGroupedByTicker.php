<?php

declare(strict_types=1);

namespace App\Services\Deals;

use App\Response\DTO\Deals\ClosedDealInGroupDTO;
use App\Response\DTO\Deals\ClosedDealListGroupByTickerDTO;

class ClosedDealsGroupedByTicker
{
    /**
     * @param DealData[] $deals
     */
    public function __construct(
        private array $deals = [],
    ) {
    }

    public function addDeal(DealData $deal): void
    {
        $this->deals[] = $deal;
    }

    public function getGroupData(): ClosedDealListGroupByTickerDTO
    {
        $firstDeal = $this->deals[0];

        $quantity = 0;
        $fullBuyPrice = '0';
        $fullSellPrice = '0';
        $profit = '0';
        $commission = '0';
        foreach ($this->deals as $deal) {
            $quantity += $deal->getQuantity();
            $fullBuyPrice = bcadd($fullBuyPrice, $deal->getFullBuyPrice(), 2);
            $profit = bcadd($profit, $deal->getProfit(), 2);
            $commission = bcadd($commission, $deal->getCommission(), 2);
            $fullSellPrice = bcadd($fullSellPrice, $deal->getFullSellPrice(), 2);
        }

        return new ClosedDealListGroupByTickerDTO(
            ticker:        $firstDeal->getTicker(),
            shortName:     $firstDeal->getName(),
            quantity:      $quantity,
            buyPrice:      bcdiv($fullBuyPrice, (string) $quantity, 4),
            fullBuyPrice:  $fullBuyPrice,
            sellPrice:     bcdiv($fullSellPrice, (string) $quantity, 4),
            fullSellPrice: $fullSellPrice,
            profit:        $profit,
            profitPercent: bcmul(bcdiv($profit, $fullBuyPrice, 4), '100', 2),
            commission:    $commission,
            currency:      $firstDeal->getCurrencyName(),
            isShort:       $firstDeal->getType() === DealType::Short,
            isBlocked:     $firstDeal->getStatus() === DealStatus::Blocked,
        );
    }

    public function getDeals(): array
    {
        return array_map(
            fn($deal) => new ClosedDealInGroupDTO(
                id:            $deal->getId(),
                accountId:     $deal->getAccountId(),
                ticker:        $deal->getTicker(),
                shortName:     $deal->getName(),
                quantity:      $deal->getQuantity(),
                buyPrice:      $deal->getBuyPrice(),
                fullBuyPrice:  $deal->getFullBuyPrice(),
                sellPrice:     $deal->getSellPrice(),
                fullSellPrice: $deal->getFullSellPrice(),
                profit:        $deal->getProfit(),
                profitPercent: $deal->getProfitPercent(),
                commission:    $deal->getCommission(),
                currency:      $deal->getCurrencyName(),
                isShort:       $deal->getType() === DealType::Short,
                isBlocked:     $deal->getStatus() === DealStatus::Blocked,
                createdAt:     $deal->getCreatedAt(),
                updatedAt:     $deal->getUpdatedAt() ?? '',
                closingDate:   $deal->getClosingDate() ?? '',
            ),
            $this->deals
        );
    }
}
