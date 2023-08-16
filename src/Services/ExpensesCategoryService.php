<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Expense;
use App\Entity\User;
use App\Repository\ExpensesCategoryRepository;
use App\Response\DTO\Expenses\ExpenseCategoryDTO;
use App\Response\DTO\Expenses\ExpenseResponseDTO;
use Doctrine\Common\Collections\Criteria;

class ExpensesCategoryService
{
    public function __construct(
        private ExpensesCategoryRepository $categoryRepository
    ) {
    }

    public function getCategories(?User $user = null): array
    {
        $categories = $this->categoryRepository->findBy(
            ['user_id' => $user?->getId()],
            ['name' => Criteria::DESC]
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
