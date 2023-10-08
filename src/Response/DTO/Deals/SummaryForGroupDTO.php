<?php

declare(strict_types=1);

namespace App\Response\DTO\Deals;

class SummaryForGroupDTO
{
    public function __construct(
        public float $buyPrice,
        public float $currentPrice,
        public float $profit,
        public float $profitPercent,
        public bool $isBaseCurrency,
        public float $buyPriceConverted,
        public float $currentPriceConverted,
        public float $profitConverted,
    ) {
    }
}
