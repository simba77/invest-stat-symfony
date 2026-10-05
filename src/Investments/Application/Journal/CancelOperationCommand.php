<?php

declare(strict_types=1);

namespace App\Investments\Application\Journal;

use App\Shared\Domain\User;

final readonly class CancelOperationCommand
{
    public function __construct(
        public int $accountId,
        public int $operationId,
        public User $user,
    ) {
    }
}
