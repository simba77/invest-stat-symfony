<?php

declare(strict_types=1);

namespace App\Application\Request\DTO\Investments\Operations;

use Symfony\Component\Validator\Constraints as Assert;

class CreateDealRequestDTO
{
    public function __construct(
        #[Assert\NotBlank]
        public string $ticker,
        #[Assert\NotBlank]
        public string $stockMarket,
        #[Assert\NotBlank]
        public int $quantity,
        #[Assert\NotBlank]
        #[Assert\Type(['type' => ['numeric']])]
        public string $buyPrice,

        #[Assert\Type(['type' => ['numeric']])]
        public string $targetPrice,
        public bool $isShort
    ) {
    }
}
