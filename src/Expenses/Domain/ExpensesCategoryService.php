<?php

declare(strict_types=1);

namespace App\Expenses\Domain;

use App\Expenses\Application\Response\DTO\ExpenseCategoryDTO;
use App\Expenses\Application\Response\DTO\ExpenseResponseDTO;
use App\Expenses\Infrastructure\Persistence\Repository\ExpensesCategoryRepository;
use App\Shared\Domain\User;
use Doctrine\Common\Collections\Criteria;

class ExpensesCategoryService
{
    public function __construct(
        private readonly ExpensesCategoryRepository $categoryRepository
    ) {
    }

    public function getCategories(?User $user = null): array
    {
        $categories = $this->categoryRepository->findBy(
            ['userId' => $user?->getId()],
            ['id' => Criteria::ASC]
        );

        $items = [];
        foreach ($categories as $category) {
            $expenses = $category->getExpenses()
                ->map(fn(Expense $item) => new ExpenseResponseDTO(
                    id:   $item->getId(),
                    name: $item->getName(),
                    sum:  $item->getSum()
                ))
                ->toArray();

            $items[] = new ExpenseCategoryDTO(
                id:       $category->getId(),
                name:     $category->getName(),
                expenses: $expenses
            );
        }

        return [
            'items' => $items,
        ];
    }
}
