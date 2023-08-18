<?php

declare(strict_types=1);

namespace App\Request\DTO\Investments;

use Symfony\Component\Validator\Constraints as Assert;

class InvestmentRequestDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Date]
        public string $date,

        #[Assert\NotBlank]
        #[Assert\Range(['min' => 0, 'max' => PHP_INT_MAX])]
        public float $sum,

        #[Assert\NotBlank]
        public int $account
    ) {
    }
}
