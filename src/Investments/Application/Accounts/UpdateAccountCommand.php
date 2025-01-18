<?php

declare(strict_types=1);

namespace App\Investments\Application\Accounts;

use App\Investments\Domain\Accounts\Account;

class UpdateAccountCommand
{
    public function __construct(
        public Account $account,
        public string $name,
        public string $balance,
        public string $usdBalance,
        public string $commission,
        public string $futuresCommission,
        public int $sort,
    ) {
    }
}
