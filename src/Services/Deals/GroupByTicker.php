<?php

declare(strict_types=1);

namespace App\Services\Deals;

use App\Response\DTO\Deals\DealInGroupDTO;
use App\Response\DTO\Deals\DealListGroupByTickerDTO;

class GroupByTicker
{
    /**
     * @param DealData[] $deals
     */
    public function __construct(
        private readonly float $accountValue = 0,
        private array $deals = [],
    ) {
    }

    public function addDeal(DealData $deal): void
    {
        $this->deals[] = $deal;
    }

    public function getGroupData(): DealListGroupByTickerDTO
    {
        $firstDeal = $this->deals[0];

        $quantity = 0;
        $fullBuyPrice = 0;
        $fullCurrentPrice = 0;
        $fullCurrentPriceInBaseCurrency = 0;
        $fullPrevPrice = 0;
        $fullTargetPrice = 0;
        $profit = 0;
        $fullTargetProfit = 0;
        $fullDailyProfit = 0;
        $commission = 0;
        foreach ($this->deals as $deal) {
            $quantity += $deal->getQuantity();
            $fullBuyPrice += $deal->getFullBuyPrice();
            $fullCurrentPrice += $deal->getFullCurrentPrice();
            $fullCurrentPriceInBaseCurrency += $deal->getFullCurrentPriceInBaseCurrency();
            $fullPrevPrice += $deal->getFullPrevPrice();
            $fullTargetPrice += $deal->getFullTargetPrice();
            $profit += $deal->getProfit();
            $fullDailyProfit += $deal->getFullDailyProfit();
            $commission += $deal->getCommission();
            $fullTargetProfit += $deal->getFullTargetProfit();
        }

        return new DealListGroupByTickerDTO(
            ticker:              $firstDeal->getTicker(),
            shortName:           $firstDeal->getName(),
            quantity:            $quantity,
            buyPrice:            round($fullBuyPrice / $quantity, 4),
            fullBuyPrice:        $fullBuyPrice,
            currentPrice:        $firstDeal->getCurrentPrice(),
            prevPrice:           $firstDeal->getPrevPrice(),
            fullCurrentPrice:    $fullCurrentPrice,
            fullPrevPrice:       $fullPrevPrice,
            targetPrice:         round($fullTargetPrice / $quantity, 4),
            fullTargetPrice:     $fullTargetPrice,
            profit:              $profit,
            profitPercent:       round($profit / $fullBuyPrice * 100, 2),
            dailyProfit:         $firstDeal->getDailyProfit(),
            fullDailyProfit:     round($fullDailyProfit, 2),
            commission:          round($commission, 2),
            targetProfit:        round($fullTargetProfit / $quantity, 4),
            fullTargetProfit:    $fullTargetProfit,
            targetProfitPercent: round($fullTargetProfit / $fullBuyPrice * 100, 2),
            percent:             round($fullCurrentPriceInBaseCurrency / $this->accountValue * 100, 2),
            currency:            $firstDeal->getCurrencyName(),
            isShort:             $firstDeal->getType() === DealType::Short,
            isBlocked:           $firstDeal->getStatus() === DealStatus::Blocked,
        );
    }

    public function getDeals(): array
    {
        return array_map(
            fn($deal) => new DealInGroupDTO(
                id:                      $deal->getId(),
                accountId:               $deal->getAccountId(),
                ticker:                  $deal->getTicker(),
                shortName:               $deal->getName(),
                quantity:                $deal->getQuantity(),
                buyPrice:                $deal->getBuyPrice(),
                fullBuyPrice:            $deal->getFullBuyPrice(),
                currentPrice:            $deal->getCurrentPrice(),
                prevPrice:               $deal->getPrevPrice(),
                fullCurrentPrice:        $deal->getFullCurrentPrice(),
                fullPrevPrice:           $deal->getFullPrevPrice(),
                targetPrice:             $deal->getTargetPrice(),
                fullTargetPrice:         $deal->getFullTargetPrice(),
                profit:                  $deal->getProfit(),
                profitPercent:           $deal->getProfitPercent(),
                dailyProfit:             $deal->getDailyProfit(),
                fullDailyProfit:         $deal->getFullDailyProfit(),
                commission:              $deal->getCommission(),
                targetProfit:            $deal->getTargetProfit(),
                fullTargetProfit:        $deal->getFullTargetProfit(),
                fullTargetProfitPercent: $deal->getTargetProfitPercent(), // Maybe bug
                percent:                 round($deal->getFullCurrentPrice() / $this->accountValue * 100, 2),
                currency:                $deal->getCurrencyName(),
                isShort:                 $deal->getType() === DealType::Short,
                isBlocked:               $deal->getStatus() === DealStatus::Blocked,
                createdAt:               $deal->getCreatedAt(),
                updatedAt:               $deal->getUpdatedAt() ?? '',
            ),
            $this->deals
        );
    }
}
