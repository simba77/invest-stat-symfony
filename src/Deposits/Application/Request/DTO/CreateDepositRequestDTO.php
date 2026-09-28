<?php

declare(strict_types=1);

namespace App\Deposits\Application\Request\DTO;

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
        #[Assert\Choice(choices: [1, 2])] // 1 - deposit, 2 - percent
        public int $type,

        #[Assert\NotBlank]
        #[Assert\Date]
        public string $date,
    ) {
    }
}
