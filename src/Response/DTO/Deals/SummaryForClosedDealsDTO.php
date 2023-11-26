<?php

declare(strict_types=1);

namespace App\Response\DTO\Deals;

class SummaryForClosedDealsDTO
{
    public function __construct(
        public float $buyPrice,
        public float $sellPrice,
        public float $profit,
        public float $profitPercent,
    ) {
    }
}
