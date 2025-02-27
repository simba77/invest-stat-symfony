<?php

declare(strict_types=1);

namespace App\Expenses\Application\Response\Compiler;

use App\Expenses\Application\Response\DTO\ExpenseCategoryDTO;
use App\Expenses\Application\Response\DTO\ExpenseResponseDTO;
use App\Expenses\Domain\Expense;
use App\Expenses\Domain\ExpensesCategory;
use App\Shared\Infrastructure\Compiler\CompilerInterface;

/**
 * @template-implements CompilerInterface<iterable<ExpensesCategory>, iterable<ExpenseCategoryDTO>>
 */
class CategoriesListCompiler implements CompilerInterface
{
    /**
     * @param ExpensesCategory[] $entry
     * @return ExpenseCategoryDTO[]
     */
    #[\Override]
    public function compile(mixed $entry): array
    {
        $items = [];
        foreach ($entry as $category) {
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

        return $items;
    }
}
