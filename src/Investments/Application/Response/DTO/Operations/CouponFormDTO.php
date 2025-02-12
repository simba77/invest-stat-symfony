<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\DTO\Operations;

class CouponFormDTO
{
    public function __construct(
        public int $id,
        public string $amount,
        public string $date,
        public int $accountId,
        public string $ticker,
        public string $stockMarket,
    ) {
    }
}
