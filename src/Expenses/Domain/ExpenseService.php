<?php

declare(strict_types=1);

namespace App\Expenses\Domain;

use App\Expenses\Application\Response\DTO\ExpenseResponseDTO;
use App\Expenses\Infrastructure\Persistence\Repository\ExpenseRepository;
use App\Shared\Domain\User;

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
