<?php

declare(strict_types=1);

namespace App\Expenses\Domain;

use App\Shared\Domain\User;
use Symfony\Component\HttpFoundation\JsonResponse;

interface ExpensesCategoryRepositoryInterface
{
    /**
     * @param User $user
     * @return ExpensesCategory[]
     */
    public function getCategoriesForUser(User $user): array;

    public function getByIdAndUser(int $id, User $user): ?ExpensesCategory;

    public function save(ExpensesCategory $expensesCategory): void;

    public function remove(ExpensesCategory $expensesCategory): void;
}
