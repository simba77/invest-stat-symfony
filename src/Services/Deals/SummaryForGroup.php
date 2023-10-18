<?php

declare(strict_types=1);

namespace App\Services\Deals;

use App\Response\DTO\Deals\SummaryForGroupDTO;

class SummaryForGroup
{
    public array $deals = [];

    public function addDeal(string $status, string $type, string $currency, DealData $deal): void
    {
        $this->deals[$status][$type][$currency][] = $deal;
    }

    public function getSummary(): array
    {
        $summary = [];
        foreach ($this->deals as $status => $statusGroup) {
            foreach ($statusGroup as $type => $typeGroup) {
                foreach ($typeGroup as $currency => $currencyGroup) {
                    $buyPrice = 0;
                    $buyPriceInBaseCurrency = 0;
                    $currentPrice = 0;
                    $currentPriceInBaseCurrency = 0;
                    $profit = 0;
                    $profitInBaseCurrency = 0;

                    foreach ($currencyGroup as $deal) {
                        $buyPrice += $deal->getFullBuyPrice();
                        $buyPriceInBaseCurrency += $deal->getFullBuyPriceInBaseCurrency();
                        $currentPrice += $deal->getFullCurrentPrice();
                        $currentPriceInBaseCurrency += $deal->getFullCurrentPriceInBaseCurrency();
                        $profit += $deal->getProfit();
                        $profitInBaseCurrency += $deal->getProfitInBaseCurrency();
                    }

                    $summary[$status][$type][$currency] = new SummaryForGroupDTO(
                        buyPrice:                   $buyPrice,
                        buyPriceInBaseCurrency:     $buyPriceInBaseCurrency,
                        currentPrice:               $currentPrice,
                        currentPriceInBaseCurrency: $currentPriceInBaseCurrency,
                        profit:                     $profit,
                        profitInBaseCurrency:       $profitInBaseCurrency,
                        profitPercent:              round($profit / $buyPrice * 100, 2),
                        isBaseCurrency:             $currency === 'RUB',
                        buyPriceConverted:          $buyPrice,
                        currentPriceConverted:      $currentPrice,
                        profitConverted:            $profit
                    );
                }
            }
        }

        return $summary;
    }
}
