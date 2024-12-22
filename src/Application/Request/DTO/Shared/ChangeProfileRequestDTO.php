<?php

declare(strict_types=1);

namespace App\Application\Request\DTO\Shared;

use Symfony\Component\Validator\Constraints as Assert;

class ChangeProfileRequestDTO
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 200)]
    public string $name;

    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 200)]
    public string $email;

    public ?string $password;

    #[Assert\Type(['type' => ['numeric']])]
    public string $salary;
}
