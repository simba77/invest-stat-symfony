<?php

declare(strict_types=1);

namespace App\Response\DTO\Dividends;

class DividendListItemDTO
{
    public function __construct(
        public int $id,
        public string $date,
        public string $ticker,
        public string $amount,
        public string $accountName
    ) {
    }
}
