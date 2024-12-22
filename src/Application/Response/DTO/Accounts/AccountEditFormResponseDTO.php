<?php

declare(strict_types=1);

namespace App\Application\Response\DTO\Accounts;

class AccountEditFormResponseDTO
{
    public function __construct(
        public string $name,
        public string $balance,
        public string $usdBalance,
        public string $commission,
        public string $futuresCommission,
        public int $sort,
    ) {
    }
}
