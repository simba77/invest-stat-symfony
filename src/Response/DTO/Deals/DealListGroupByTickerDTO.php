<?php

declare(strict_types=1);

namespace App\Response\DTO\Deals;

class DealListGroupByTickerDTO
{
    public function __construct(
        public string $ticker,
        public string $shortName,
        public int $quantity,
        public float $buyPrice,
        public float $fullBuyPrice,
        public float $currentPrice,
        public float $prevPrice,
        public float $fullCurrentPrice,
        public float $fullPrevPrice,
        public float $targetPrice,
        public float $fullTargetPrice,
        public float $profit,
        public float $profitPercent,
        public float $dailyProfit,
        public float $fullDailyProfit,
        public float $commission,
        public float $targetProfit,
        public float $fullTargetProfit,
        public float $targetProfitPercent,
        public float $percent,
        public string $currency,
        public bool $isShort,
        public bool $isBlocked,
    ) {
    }
}
