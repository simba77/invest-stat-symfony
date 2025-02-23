<?php

declare(strict_types=1);

namespace App\Investments\Application\Operations\Deals;

class UpdateDealCommand
{
    public function __construct(
        public int $id,
        public string $ticker,
        public string $stockMarket,
        public bool $isShort,
        public int $quantity,
        public string $buyPrice,
        public string $targetPrice,
    ) {
    }
}
