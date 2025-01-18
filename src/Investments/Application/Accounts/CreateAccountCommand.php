<?php

declare(strict_types=1);

namespace App\Investments\Application\Accounts;

use App\Shared\Domain\User;

class CreateAccountCommand
{
    public function __construct(
        public User $user,
        public string $name,
        public string $balance,
        public string $usdBalance,
        public string $commission,
        public string $futuresCommission,
        public int $sort,
    ) {
    }
}
