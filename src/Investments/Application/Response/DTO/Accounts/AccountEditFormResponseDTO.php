<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\DTO\Accounts;

/**
 * @psalm-api
 */
class AccountEditFormResponseDTO
{
    public function __construct(
        public string $name,
        public string $balance,
        public string $usdBalance,
        public string $blockedBalance,
        public string $blockedUsdBalance,
        public string $commission,
        public string $futuresCommission,
        public int $sort,
        public bool $isSynced,
    ) {
    }
}
