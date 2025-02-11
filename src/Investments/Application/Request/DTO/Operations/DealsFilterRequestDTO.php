<?php

declare(strict_types=1);

namespace App\Investments\Application\Request\DTO\Operations;

use Symfony\Component\Validator\Constraints as Assert;

final class DealsFilterRequestDTO
{
    public function __construct(
        #[Assert\Length(min: 0)]
        public ?int $accountId = null,

        #[Assert\DateTime(options: ['format' => 'd.m.Y'])]
        public ?string $startDate = null,

        #[Assert\DateTime(options: ['format' => 'd.m.Y'])]
        public ?string $endDate = null,
    ) {
    }
}
