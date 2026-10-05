<?php

declare(strict_types=1);

namespace App\Investments\Application\Request\DTO\Operations;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CorrectSaleRequestDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Type(['type' => ['numeric']])]
        #[Assert\PositiveOrZero]
        public string $price,
        #[Assert\NotBlank]
        #[Assert\DateTime(format: \DateTimeInterface::ATOM)]
        public string $executedAt,
    ) {
    }
}
