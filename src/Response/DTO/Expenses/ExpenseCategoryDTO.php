<?php

declare(strict_types=1);

namespace App\Response\DTO\Expenses;

final class ExpenseCategoryDTO
{
    public function __construct(
        public int $id,
        public string $name
    ) {
    }
}
