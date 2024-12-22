<?php

declare(strict_types=1);

namespace App\Application\Response\DTO\Investments\Analytics;

class ClosedDealInGroupDTO
{
    public function __construct(
        public int $id,
        public int $accountId,
        public string $ticker,
        public string $shortName,
        public int $quantity,
        public string $buyPrice,
        public string $fullBuyPrice,
        public string $sellPrice,
        public string $fullSellPrice,
        public string $profit,
        public string $profitPercent,
        public string $commission,
        public string $currency,
        public bool $isShort,
        public bool $isBlocked,
        public string $createdAt,
        public string $updatedAt,
        public string $closingDate,
    ) {
    }
}
