<?php

declare(strict_types=1);

namespace App\Response\DTO\Expenses;

class ExpenseResponseDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public float $sum
    ) {
    }
}
