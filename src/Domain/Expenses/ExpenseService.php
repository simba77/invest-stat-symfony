<?php

declare(strict_types=1);

namespace App\Domain\Expenses;

use App\Application\Response\DTO\Expenses\ExpenseResponseDTO;
use App\Domain\Shared\User;
use App\Infrastructure\Persistence\Repository\ExpenseRepository;

class ExpenseService
{
    public function __construct(
        private readonly ExpenseRepository $expenseRepository
    ) {
    }

    public function getByIdAndUser(int $id, ?User $user = null): ?ExpenseResponseDTO
    {
        $expense = $this->expenseRepository->findOneBy(['id' => $id, 'userId' => $user?->getId()]);
        if ($expense) {
            return new ExpenseResponseDTO($expense->getId(), $expense->getName(), $expense->getSum());
        }
        return null;
    }
}
