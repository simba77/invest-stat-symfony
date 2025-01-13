<?php

declare(strict_types=1);

namespace App\Expenses\Application;

use App\Expenses\Domain\Expense;
use App\Expenses\Domain\ExpenseRepositoryInterface;
use App\Expenses\Domain\ExpensesCategoryRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CreateExpenseCommandHandler
{
    public function __construct(
        public ExpensesCategoryRepositoryInterface $expensesCategoryRepository,
        public ExpenseRepositoryInterface $expenseRepository,
    ) {
    }

    public function __invoke(CreateExpenseCommand $createExpenseCommand): void
    {
        $category = $this->expensesCategoryRepository->getByIdAndUser($createExpenseCommand->categoryId, $createExpenseCommand->user);
        $expense = new Expense(
            $createExpenseCommand->name,
            $createExpenseCommand->amount,
            $createExpenseCommand->user->getId()
        );
        $expense->setCategory($category);
        $this->expenseRepository->save($expense);
    }
}
