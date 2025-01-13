<?php

declare(strict_types=1);

namespace App\Expenses\Application;

use App\Shared\Domain\User;

class CreateExpenseCommand
{
    public function __construct(
        public string $name,
        public string $amount,
        public int $categoryId,
        public User $user,
    ) {
    }
}
