<?php

declare(strict_types=1);

namespace App\Response\DTO\Deposits;

class DepositListItemDTO
{
    public function __construct(
        public int $id,
        public string $date,
        public string $sum,
        public string $typeName,
        public string $accountName
    ) {
    }
}
