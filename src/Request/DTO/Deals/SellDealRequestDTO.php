<?php

declare(strict_types=1);

namespace App\Request\DTO\Deals;

use Symfony\Component\Validator\Constraints as Assert;

class SellDealRequestDTO
{
    public function __construct(
        public ?int $id,
        #[Assert\NotBlank]
        public int $accountId,
        #[Assert\NotBlank]
        public string $ticker,
        #[Assert\NotBlank]
        public float $price,
        #[Assert\NotBlank]
        public int $quantity,
    ) {
    }
}
