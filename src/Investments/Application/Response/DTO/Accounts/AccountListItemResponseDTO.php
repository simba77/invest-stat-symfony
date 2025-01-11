<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\DTO\Accounts;

class AccountListItemResponseDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $balance,
        public string $usdBalance,
        public string $deposits,
        public string $currentValue,
        public string $fullProfit,
    ) {
    }
}
