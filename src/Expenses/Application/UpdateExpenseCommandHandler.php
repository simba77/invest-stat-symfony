<?php

declare(strict_types=1);

namespace App\Expenses\Application;

use App\Expenses\Domain\ExpenseRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateExpenseCommandHandler
{
    public function __construct(
        public ExpenseRepositoryInterface $expenseRepository,
    ) {
    }

    public function __invoke(UpdateExpenseCommand $updateExpenseCommand): void
    {
        $expense = $updateExpenseCommand->expense;
        $expense->setName($updateExpenseCommand->name);
        $expense->setSum($updateExpenseCommand->amount);
        $this->expenseRepository->save($expense);
    }
}
