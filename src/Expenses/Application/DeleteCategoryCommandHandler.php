<?php

namespace App\Expenses\Application;

use App\Expenses\Domain\ExpenseRepositoryInterface;
use App\Expenses\Domain\ExpensesCategoryRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DeleteCategoryCommandHandler
{
    public function __construct(
        public ExpensesCategoryRepositoryInterface $expensesCategoryRepository,
        public ExpenseRepositoryInterface $expenseRepository
    ) {
    }

    public function __invoke(DeleteCategoryCommand $deleteCategory): void
    {
        // Remove related expenses
        $expenses = $deleteCategory->category->getExpenses();
        foreach ($expenses as $expense) {
            $this->expenseRepository->remove($expense);
        }

        $this->expensesCategoryRepository->remove($deleteCategory->category);
    }
}
