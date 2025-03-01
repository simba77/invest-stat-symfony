<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\DTO\Operations;

/**
 * @psalm-api
 */
class DealListGroupByTickerDTO
{
    public function __construct(
        public string $ticker,
        public string $shortName,
        public int $quantity,
        public string $buyPrice,
        public string $fullBuyPrice,
        public string $currentPrice,
        public string $prevPrice,
        public string $fullCurrentPrice,
        public string $fullPrevPrice,
        public string $targetPrice,
        public string $fullTargetPrice,
        public string $profit,
        public string $profitPercent,
        public string $dailyProfit,
        public string $fullDailyProfit,
        public string $commission,
        public string $targetProfit,
        public string $fullTargetProfit,
        public string $targetProfitPercent,
        public string $percent,
        public string $currency,
        public bool $isShort,
        public bool $isBlocked,
    ) {
    }
}
