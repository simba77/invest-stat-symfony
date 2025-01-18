<?php

declare(strict_types=1);

namespace App\Investments\Application\Accounts;

use App\Shared\Domain\User;

class DeleteAccountCommand
{
    public function __construct(
        public int $accountId,
        public User $user,
    ) {
    }
}
