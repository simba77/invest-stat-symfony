<?php

declare(strict_types=1);

namespace App\Application\Response\DTO\Investments\Operations;

class DividendListItemDTO
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
