<?php

declare(strict_types=1);

namespace App\Tests\Expenses;

use App\Expenses\Domain\Expense;
use App\Expenses\Domain\ExpensesCategory;
use App\Shared\Domain\User;

/**
 * @psalm-require-extends \App\Tests\Support\ApiTestCase
 */
trait CreatesExpenses
{
    private function createCategory(User $owner, string $name = 'Food'): ExpensesCategory
    {
        $category = new ExpensesCategory($name, $owner->getId());
        $this->persist($category);

        return $category;
    }

    private function createExpense(ExpensesCategory $category, string $name = 'Groceries', string $sum = '100.00'): Expense
    {
        $expense = new Expense($name, $sum, (int) $category->getUserId());
        $expense->setCategory($category);
        $this->persist($expense);

        return $expense;
    }
}
