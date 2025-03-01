<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\DTO\Operations;

/**
 * @psalm-api
 */
class EditDealDTO
{
    public function __construct(
        public int $id,
        public string $ticker,
        public string $stockMarket,
        public int $quantity,
        public string $buyPrice,
        public string $targetPrice,
        public bool $isShort,
    ) {
    }
}
