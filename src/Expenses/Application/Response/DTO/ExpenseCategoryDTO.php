<?php

declare(strict_types=1);

namespace App\Expenses\Application\Response\DTO;

/**
 * @psalm-api
 */
final class ExpenseCategoryDTO
{
    public function __construct(
        public int $id,
        public string $name,
        /** @var ExpenseResponseDTO[] */
        public array $expenses
    ) {
    }
}
