<?php

declare(strict_types=1);

namespace App\Domain\Investments\Operations\Deals;

use App\Application\Response\DTO\Investments\Operations\SummaryForGroupDTO;

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
                    $buyPrice = '0';
                    $buyPriceInBaseCurrency = '0';
                    $currentPrice = '0';
                    $currentPriceInBaseCurrency = '0';
                    $profit = '0';
                    $profitInBaseCurrency = '0';

                    foreach ($currencyGroup as $deal) {
                        $buyPrice = bcadd($buyPrice, $deal->getFullBuyPrice(), 4);
                        $buyPriceInBaseCurrency = bcadd($buyPriceInBaseCurrency, $deal->getFullBuyPriceInBaseCurrency(), 4);
                        $currentPrice = bcadd($currentPrice, $deal->getFullCurrentPrice(), 4);
                        $currentPriceInBaseCurrency = bcadd($currentPriceInBaseCurrency, $deal->getFullCurrentPriceInBaseCurrency(), 4);
                        $profit = bcadd($profit, $deal->getProfit(), 4);
                        $profitInBaseCurrency = bcadd($profitInBaseCurrency, $deal->getProfitInBaseCurrency(), 4);
                    }

                    $summary[$status][$type][$currency] = new SummaryForGroupDTO(
                        buyPrice:                   $buyPrice,
                        buyPriceInBaseCurrency:     $buyPriceInBaseCurrency,
                        currentPrice:               $currentPrice,
                        currentPriceInBaseCurrency: $currentPriceInBaseCurrency,
                        profit:                     $profit,
                        profitInBaseCurrency:       $profitInBaseCurrency,
                        profitPercent:              bcmul(bcdiv($profit, $buyPrice, 4), '100', 2),
                        isBaseCurrency:             $currency === 'RUB',
                    );
                }
            }
        }

        return $summary;
    }
}
