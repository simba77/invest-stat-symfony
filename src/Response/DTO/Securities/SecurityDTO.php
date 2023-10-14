<?php

declare(strict_types=1);

namespace App\Response\DTO\Securities;

use App\Services\MarketData\Securities\SecurityTypeEnum;

class SecurityDTO
{
    public function __construct(
        public string $ticker,
        public string $shortName,
        public string $stockMarket,
        public float $price,
        public int | float $lotSize,
        public string $currency,
        public SecurityTypeEnum $securityType,
        public float $bondAccumulatedCoupon = 0,
    ) {
    }
}
