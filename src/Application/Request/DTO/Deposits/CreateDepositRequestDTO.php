<?php

declare(strict_types=1);

namespace App\Application\Request\DTO\Deposits;

use Symfony\Component\Validator\Constraints as Assert;

class CreateDepositRequestDTO
{
    public function __construct(
        #[Assert\NotBlank]
        public int $accountId,

        #[Assert\NotBlank]
        #[Assert\Type(['type' => ['numeric']])]
        public string $sum,

        #[Assert\NotBlank]
        public int $type,

        #[Assert\NotBlank]
        #[Assert\Date]
        public string $date,
    ) {
    }
}
