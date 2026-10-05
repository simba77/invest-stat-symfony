<?php

declare(strict_types=1);

namespace App\Investments\Application\Accounts;

use App\Shared\Domain\User;

final readonly class CloseAccountCommand
{
    public function __construct(
        public int $accountId,
        public User $user,
        public bool $close = true,
    ) {
    }
}
