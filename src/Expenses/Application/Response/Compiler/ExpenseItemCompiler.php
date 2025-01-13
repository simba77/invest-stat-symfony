<?php

declare(strict_types=1);

namespace App\Expenses\Application\Response\Compiler;

use App\Expenses\Application\Response\DTO\ExpenseResponseDTO;
use App\Expenses\Domain\Expense;
use App\Shared\Infrastructure\Compiler\CompilerInterface;

/**
 * @template-implements CompilerInterface<Expense, ExpenseResponseDTO>
 */
class ExpenseItemCompiler implements CompilerInterface
{
    /**
     * @param Expense $entry
     * @return ExpenseResponseDTO
     */
    public function compile(mixed $entry): ExpenseResponseDTO
    {
        return new ExpenseResponseDTO(
            id:   $entry->getId(),
            name: $entry->getName(),
            sum:  $entry->getSum()
        );
    }
}
