<?php

declare(strict_types=1);

namespace App\Request\DTO\Profile;

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

    #[Assert\NotBlank]
    public float $salary;
}
