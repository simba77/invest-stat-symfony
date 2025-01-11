<?php

declare(strict_types=1);

namespace App\Deposits\Application\Request\DTO;

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
