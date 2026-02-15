<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\DTO\Instruments;

use App\Investments\Domain\Instruments\PriceTrendEnum;

final readonly class ShowSharePortfolioDTO
{
    public function __construct(
        public int $quantity,
        public string $fullPrice,
        public string $fullProfit,
        public string $fullProfitPercent,
        public PriceTrendEnum $fullProfitTrend,
        public string $averageBuyPrice,
        public string $portfolioPercent,
        public string $closedDealsProfit,
        public string $closedDealsProfitPercent,
        public PriceTrendEnum $closedDealsProfitTrend,
    ) {
    }
}
