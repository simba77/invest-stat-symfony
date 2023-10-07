<?php

declare(strict_types=1);

namespace App\Response\DTO\Accounts;

class AccountDetailResponseDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public float $balance,
        public float $usdBalance,
        public float $deposits,
        public float $currentValue,
        public float $fullProfit,
    ) {
    }
}
