<?php

declare(strict_types=1);

namespace App\Expenses\Domain;

interface ExpenseRepositoryInterface
{
    public function getSumForUser(int $userId): string;

    public function save(Expense $expense): void;

    public function remove(Expense $expense): void;
}
