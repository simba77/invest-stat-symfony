<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\DTO\Instruments;

use App\Investments\Domain\Instruments\PriceTrendEnum;

final readonly class ShowShareResponseDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $ticker,
        public ?string $logo,
        public string $marketName,
        public string $currency,
        public string $currencyCode,
        public string $price,
        public string $prevPrice,
        public string $difference,
        public string $percent,
        public string $lotSize,
        public string $isin,
        public PriceTrendEnum $priceTrend,
    ) {
    }
}
