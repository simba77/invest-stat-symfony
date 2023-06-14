<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\ExpensesCategory;
use App\Entity\User;
use App\Models\ExpensesCategoryListItem;
use App\Models\ExpensesCategoryListResponse;
use App\Repository\ExpensesCategoryRepository;
use Doctrine\Common\Collections\Criteria;

class ExpensesCategoryService
{
    public function __construct(
        private ExpensesCategoryRepository $categoryRepository
    ) {
    }

    public function getCategories(?User $user = null): ExpensesCategoryListResponse
    {
        $categories = $this->categoryRepository->findBy(
            ['user_id' => $user?->getId()],
            ['name' => Criteria::DESC]
        );

        $items = array_map(fn(ExpensesCategory $category) => new ExpensesCategoryListItem(
            id: $category->getId(), name: $category->getName()
        ), $categories);

        return new ExpensesCategoryListResponse($items);
    }
}
