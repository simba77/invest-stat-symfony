<?php

declare(strict_types=1);

namespace App\Deposits\Application;

use App\Deposits\Domain\Deposit;
use App\Deposits\Domain\DepositAccount;

class UpdateDepositCommand
{
    public function __construct(
        public Deposit $deposit,
        public string $amount,
        public int $type,
        public string $date,
        public DepositAccount $account,
    ) {
    }
}
