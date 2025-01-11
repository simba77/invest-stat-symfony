<?php

declare(strict_types=1);

namespace App\Expenses\Domain;

use App\Shared\Domain\User;

interface ExpensesCategoryRepositoryInterface
{
    /**
     * @param User $user
     * @return ExpensesCategory[]
     */
    public function getCategoriesForUser(User $user): array;

    public function save(ExpensesCategory $expensesCategory): void;

    public function remove(ExpensesCategory $expensesCategory): void;
}
