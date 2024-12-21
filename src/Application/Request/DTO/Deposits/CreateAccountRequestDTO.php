<?php

declare(strict_types=1);

namespace App\Application\Request\DTO\Deposits;

use Symfony\Component\Validator\Constraints as Assert;

class CreateAccountRequestDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 200)]
        public string $name,
    ) {
    }
}
