<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\DTO\Operations;

/**
 * @psalm-api
 */
class CouponListItemDTO
{
    public function __construct(
        public int $id,
        public string $date,
        public string $ticker,
        public string $stockMarket,
        public string $amount,
        public string $accountName
    ) {
    }
}
