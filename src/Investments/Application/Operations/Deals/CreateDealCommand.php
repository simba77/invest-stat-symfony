<?php

declare(strict_types=1);

namespace App\Investments\Application\Operations\Deals;

class CreateDealCommand
{
    public function __construct(
        public int $accountId,
        public int $userId,
        public string $ticker,
        public string $stockMarket,
        public bool $isShort,
        public int $quantity,
        public string $buyPrice,
        public string $targetPrice,
    ) {
    }
}
