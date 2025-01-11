<?php

declare(strict_types=1);

namespace App\Expenses\Application\Request\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateCategoryRequestDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 200)]
        public string $name
    ) {
    }
}
