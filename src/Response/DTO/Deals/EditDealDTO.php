<?php

declare(strict_types=1);

namespace App\Response\DTO\Deals;

class EditDealDTO
{
    public function __construct(
        public int $id,
        public string $ticker,
        public string $stockMarket,
        public int $quantity,
        public float $buyPrice,
        public float $targetPrice,
        public bool $isShort,
    ) {
    }
}
