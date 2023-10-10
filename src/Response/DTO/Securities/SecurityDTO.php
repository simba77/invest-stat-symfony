<?php

declare(strict_types=1);

namespace App\Response\DTO\Securities;

class SecurityDTO
{
    public function __construct(
        public string $ticker,
        public string $shortName,
        public string $stockMarket,
        public float $price,
        public int $lotSize,
    ) {
    }
}
