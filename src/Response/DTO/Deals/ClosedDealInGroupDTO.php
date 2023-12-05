<?php

declare(strict_types=1);

namespace App\Response\DTO\Deals;

class ClosedDealInGroupDTO
{
    public function __construct(
        public int $id,
        public int $accountId,
        public string $ticker,
        public string $shortName,
        public int $quantity,
        public float $buyPrice,
        public float $fullBuyPrice,
        public float $sellPrice,
        public float $fullSellPrice,
        public float $profit,
        public float $profitPercent,
        public float $commission,
        public string $currency,
        public bool $isShort,
        public bool $isBlocked,
        public string $createdAt,
        public string $updatedAt,
        public string $closingDate,
    ) {
    }
}
