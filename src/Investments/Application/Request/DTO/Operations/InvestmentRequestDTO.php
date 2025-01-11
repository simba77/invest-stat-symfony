<?php

declare(strict_types=1);

namespace App\Investments\Application\Request\DTO\Operations;

use Symfony\Component\Validator\Constraints as Assert;

class InvestmentRequestDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Date]
        public string $date,

        #[Assert\NotBlank]
        #[Assert\Type(['type' => ['numeric']])]
        public string $sum,

        #[Assert\NotBlank]
        public int $account
    ) {
    }
}
