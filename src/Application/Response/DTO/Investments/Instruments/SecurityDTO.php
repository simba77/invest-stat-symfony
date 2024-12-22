<?php

declare(strict_types=1);

namespace App\Application\Response\DTO\Investments\Instruments;

use App\Domain\Investments\Instruments\Securities\SecurityTypeEnum;

class SecurityDTO
{
    public function __construct(
        public string $ticker,
        public string $shortName,
        public string $stockMarket,
        public string $price,
        public string $lotSize,
        public string $currency,
        public SecurityTypeEnum $securityType,
        public string $bondAccumulatedCoupon = '0',
    ) {
    }
}
