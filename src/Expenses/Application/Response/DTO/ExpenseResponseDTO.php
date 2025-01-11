<?php

declare(strict_types=1);

namespace App\Expenses\Application\Response\DTO;

class ExpenseResponseDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $sum
    ) {
    }
}
