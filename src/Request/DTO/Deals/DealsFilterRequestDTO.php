<?php

declare(strict_types=1);

namespace App\Request\DTO\Deals;

use Symfony\Component\Validator\Constraints as Assert;

class DealsFilterRequestDTO
{
    public function __construct(
        #[Assert\Length(min: 0)]
        public ?int $accountId = null,

        #[Assert\Date]
        public ?string $startDate = null,

        #[Assert\Date]
        public ?string $endDate = null,
    ) {
    }
}
