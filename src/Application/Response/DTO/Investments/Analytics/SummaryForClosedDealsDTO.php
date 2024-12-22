<?php

declare(strict_types=1);

namespace App\Application\Response\DTO\Investments\Analytics;

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
