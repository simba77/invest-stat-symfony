<?php

declare(strict_types=1);

namespace App\Request\DTO\Accounts;

use Symfony\Component\Validator\Constraints as Assert;

class CreateAccountRequestDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 200)]
        public string $name,
        public float $balance,
        public float $usdBalance,
        public float $commission,
        public float $futuresCommission,
        public int $sort,
    ) {
    }
}
