<?php

declare(strict_types=1);

namespace App\Shared\Application\Request\DTO;

use App\Shared\Domain\TaxProfile;
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

    #[Assert\NotBlank]
    #[Assert\Choice(callback: [self::class, 'getTaxProfiles'])]
    public string $taxProfile = TaxProfile::Ndfl13->value;

    /**
     * @return list<string>
     */
    public static function getTaxProfiles(): array
    {
        return array_map(static fn (TaxProfile $profile): string => $profile->value, TaxProfile::cases());
    }
}
