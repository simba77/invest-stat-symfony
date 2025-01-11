<?php

declare(strict_types=1);

namespace App\Deposits\Application;

use App\Deposits\Domain\DepositAccount;
use App\Domain\Shared\User;

class CreateDepositCommand
{
    public function __construct(
        public string $amount,
        public int $type,
        public string $date,
        public DepositAccount $account,
        public User $user,
    ) {
    }
}
