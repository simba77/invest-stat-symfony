<?php

declare(strict_types=1);

namespace App\Investments\Application\Operations\Coupons;

class CreateCouponCommand
{
    public function __construct(
        public int $userId,
        public int $accountId,
        public string $ticker,
        public string $stockMarket,
        public string $amount,
        public string $date,
    ) {
    }
}
