<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\User;
use App\Repository\ExpenseRepository;
use App\Response\DTO\Expenses\ExpenseResponseDTO;

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
