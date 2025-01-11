<?php

declare(strict_types=1);

namespace App\Investments\Application\Request\DTO\Operations;

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
        #[Assert\Type(['type' => ['numeric']])]
        public string $price,
        #[Assert\NotBlank]
        public int $quantity,
    ) {
    }
}
