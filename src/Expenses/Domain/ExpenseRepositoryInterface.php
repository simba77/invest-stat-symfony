<?php

declare(strict_types=1);

namespace App\Expenses\Domain;

use App\Shared\Domain\User;

interface ExpenseRepositoryInterface
{
    public function getSumForUser(int $userId): string;

    public function getByIdAndUser(int $id, User $user): ?Expense;

    public function save(Expense $expense): void;

    public function remove(Expense $expense): void;
}
