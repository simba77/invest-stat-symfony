<?php

declare(strict_types=1);

namespace App\Investments\Application\Request\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateAccountRequestDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 200)]
        public string $name,
        #[Assert\NotBlank]
        public string $balance = '0',
        #[Assert\NotBlank]
        public string $usdBalance = '0',
        /** The part of the rouble cash that cannot be used. */
        #[Assert\NotBlank]
        #[Assert\Type(['type' => ['numeric']])]
        #[Assert\PositiveOrZero]
        public string $blockedBalance = '0',
        #[Assert\NotBlank]
        #[Assert\Type(['type' => ['numeric']])]
        #[Assert\PositiveOrZero]
        public string $blockedUsdBalance = '0',
        #[Assert\NotBlank]
        public string $commission = '0',
        #[Assert\NotBlank]
        public string $futuresCommission = '0',
        public int $sort = 100,
    ) {
    }
}
