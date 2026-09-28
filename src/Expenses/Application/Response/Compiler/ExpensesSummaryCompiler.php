<?php

declare(strict_types=1);

namespace App\Expenses\Application\Response\Compiler;

use App\Expenses\Domain\ExpenseRepositoryInterface;
use App\Shared\Domain\User;
use App\Shared\Infrastructure\Compiler\CompilerInterface;

/**
 * @template-implements CompilerInterface<User, array<array{name: string, total: numeric-string, helpText: string}>>
 */
class ExpensesSummaryCompiler implements CompilerInterface
{
    public function __construct(
        public ExpenseRepositoryInterface $expenseRepository,
    ) {
    }

    /**
     * @param User $entry
     * @return array<array{name: string, total: numeric-string, helpText: string}>
     */
    #[\Override]
    public function compile(mixed $entry): array
    {
        $expenses = $this->expenseRepository->getSumForUser($entry->getId());
        $salary = $entry->getSalary() ?? '0';
        return [
            [
                'name'     => 'Salary',
                'total'    => $salary,
                'helpText' => 'Monthly Salary',
            ],
            [
                'name'     => 'All Expenses',
                'total'    => $expenses,
                'helpText' => 'Monthly Expenses',
            ],
            [
                'name'     => 'Salary - Expenses',
                'total'    => bcsub($salary, $expenses, 2),
                'helpText' => 'Free Money for Investments',
            ],
        ];
    }
}
