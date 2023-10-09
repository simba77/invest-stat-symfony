<?php

declare(strict_types=1);

namespace App\Request\DTO\Deals;

use Symfony\Component\Validator\Constraints as Assert;

class EditDealRequestDTO
{
    public function __construct(
        #[Assert\NotBlank]
        public string $ticker,
        #[Assert\NotBlank]
        public string $stockMarket,
        #[Assert\NotBlank]
        public int $quantity,
        #[Assert\NotBlank]
        public float $buyPrice,
        public float $targetPrice,
        public bool $isShort
    ) {
    }
}
