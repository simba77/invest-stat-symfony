<?php

declare(strict_types=1);

namespace App\Response\DTO\Deals;

class ClosedDealListGroupByTickerDTO
{
    public function __construct(
        public string $ticker,
        public string $shortName,
        public int $quantity,
        public string $buyPrice,
        public string $fullBuyPrice,
        public string $sellPrice,
        public string $fullSellPrice,
        public string $profit,
        public string $profitPercent,
        public string $commission,
        public string $currency,
        public bool $isShort,
        public bool $isBlocked,
    ) {
    }
}
