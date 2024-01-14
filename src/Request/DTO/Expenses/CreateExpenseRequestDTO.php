<?php

declare(strict_types=1);

namespace App\Request\DTO\Expenses;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateExpenseRequestDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 200)]
        public string $name,

        #[Assert\NotBlank]
        #[Assert\Type(['type' => ['numeric']])]
        public string $sum,
    ) {
    }
}
