<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\ExpensesCategory;
use App\Entity\User;
use App\Repository\ExpensesCategoryRepository;
use App\Response\DTO\Expenses\ExpenseCategoryDTO;
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
            $items[] = new ExpenseCategoryDTO(
                id: $category->getId(),
                name: $category->getName()
            );
        }

        return [
            'items' => $items,
        ];
    }
}
