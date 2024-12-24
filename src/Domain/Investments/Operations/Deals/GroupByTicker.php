<?php

declare(strict_types=1);

namespace App\Domain\Investments\Operations\Deals;

use App\Application\Response\DTO\Investments\Operations\DealInGroupDTO;
use App\Application\Response\DTO\Investments\Operations\DealListGroupByTickerDTO;

class GroupByTicker
{
    /**
     * @param DealData[] $deals
     */
    public function __construct(
        private readonly string $accountValue = '0',
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
        $fullBuyPrice = '0';
        $fullCurrentPrice = '0';
        $fullCurrentPriceInBaseCurrency = '0';
        $fullPrevPrice = '0';
        $fullTargetPrice = '0';
        $profit = '0';
        $fullTargetProfit = '0';
        $fullDailyProfit = '0';
        $commission = '0';
        foreach ($this->deals as $deal) {
            $quantity += $deal->getQuantity();
            $fullBuyPrice = bcadd($fullBuyPrice, $deal->getFullBuyPrice(), 4);
            $fullCurrentPrice = bcadd($fullCurrentPrice, $deal->getFullCurrentPrice(), 4);
            $fullCurrentPriceInBaseCurrency = bcadd($fullCurrentPriceInBaseCurrency, $deal->getFullCurrentPriceInBaseCurrency(), 4);
            $fullPrevPrice = bcadd($fullPrevPrice, $deal->getFullPrevPrice(), 4);
            $fullTargetPrice = bcadd($fullTargetPrice, $deal->getFullTargetPrice(), 4);
            $profit = bcadd($profit, $deal->getProfit(), 4);
            $fullDailyProfit = bcadd($fullDailyProfit, $deal->getFullDailyProfit(), 2);
            $commission = bcadd($commission, $deal->getCommission(), 2);
            $fullTargetProfit = bcadd($fullTargetProfit, $deal->getFullTargetProfit(), 4);
        }

        return new DealListGroupByTickerDTO(
            ticker:              $firstDeal->getTicker(),
            shortName:           $firstDeal->getName(),
            quantity:            $quantity,
            buyPrice:            bcdiv($fullBuyPrice, (string) $quantity, 4),
            fullBuyPrice:        $fullBuyPrice,
            currentPrice:        $firstDeal->getCurrentPrice(),
            prevPrice:           $firstDeal->getPrevPrice(),
            fullCurrentPrice:    $fullCurrentPrice,
            fullPrevPrice:       $fullPrevPrice,
            targetPrice:         bcdiv($fullTargetPrice, (string) $quantity, 4),
            fullTargetPrice:     $fullTargetPrice,
            profit:              $profit,
            profitPercent:       bcmul(bcdiv($profit, $fullBuyPrice, 4), '100', 2),
            dailyProfit:         $firstDeal->getDailyProfit(),
            fullDailyProfit:     $fullDailyProfit,
            commission:          $commission,
            targetProfit:        bcdiv($fullTargetProfit, (string) $quantity, 4),
            fullTargetProfit:    $fullTargetProfit,
            targetProfitPercent: bcmul(bcdiv($fullTargetProfit, $fullBuyPrice, 4), '100', 2),
            percent:             bcmul(bcdiv($fullCurrentPriceInBaseCurrency, $this->accountValue, 4), '100', 2),
            currency:            $firstDeal->getCurrencyName(),
            isShort:             $firstDeal->getType() === DealType::Short,
            isBlocked:           $firstDeal->getStatus() === DealStatus::Blocked,
        );
    }

    /**
     * @return DealInGroupDTO[]
     */
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
                percent:                 bcmul(bcdiv($deal->getFullCurrentPrice(), $this->accountValue, 4), '100', 2),
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
