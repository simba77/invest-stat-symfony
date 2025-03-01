<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\DTO\Instruments;

use App\Investments\Domain\Instruments\Securities\SecurityTypeEnum;

/**
 * @psalm-api
 */
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
