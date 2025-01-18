<?php

declare(strict_types=1);

namespace App\Expenses\Application;

use App\Expenses\Domain\Expense;

class UpdateExpenseCommand
{
    public function __construct(
        public Expense $expense,
        public string $name,
        public string $amount,
    ) {
    }
}
