<?php

declare(strict_types=1);

namespace App\Investments\Application\Request\DTO\Instruments;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateFutureMultiplierRequestDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Type(['type' => ['numeric']])]
        public string $value,

        #[Assert\NotBlank]
        public string $ticker,
    ) {
    }
}
