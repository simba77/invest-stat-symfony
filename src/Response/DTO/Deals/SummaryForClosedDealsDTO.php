<?php

declare(strict_types=1);

namespace App\Response\DTO\Deals;

class SummaryForClosedDealsDTO
{
    public function __construct(
        public string $buyPrice,
        public string $sellPrice,
        public string $profit,
        public string $profitPercent,
    ) {
    }
}
