<?php

declare(strict_types=1);

namespace App\Response\DTO\Accounts;

class AccountEditFormResponseDTO
{
    public function __construct(
        public string $name,
        public float $balance,
        public float $usdBalance,
        public float $commission,
        public float $futuresCommission,
        public int $sort,
    ) {
    }
}
