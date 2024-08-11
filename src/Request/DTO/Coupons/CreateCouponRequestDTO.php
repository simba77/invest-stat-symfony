<?php

declare(strict_types=1);

namespace App\Request\DTO\Coupons;

use Symfony\Component\Validator\Constraints as Assert;

class CreateCouponRequestDTO
{
    public function __construct(
        #[Assert\NotBlank]
        public int $accountId,

        #[Assert\NotBlank]
        #[Assert\Type(['type' => ['numeric']])]
        public string $amount,

        #[Assert\NotBlank]
        public string $ticker,

        #[Assert\NotBlank]
        public string $stockMarket,

        #[Assert\NotBlank]
        #[Assert\Date]
        public string $date,
    ) {
    }
}
