<?php

declare(strict_types=1);

namespace App\Request\DTO\Deposits;

use Symfony\Component\Validator\Constraints as Assert;

class CreateDepositRequestDTO
{
    public function __construct(
        #[Assert\NotBlank]
        public int $accountId,

        #[Assert\NotBlank]
        #[Assert\Range(['min' => PHP_INT_MIN, 'max' => PHP_INT_MAX])]
        public float $sum,

        #[Assert\NotBlank]
        public int $type,

        #[Assert\NotBlank]
        #[Assert\Date]
        public string $date,
    ) {
    }
}
