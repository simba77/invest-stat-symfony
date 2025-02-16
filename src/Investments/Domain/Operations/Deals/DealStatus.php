<?php

declare(strict_types=1);

namespace App\Investments\Domain\Operations\Deals;

enum DealStatus: int
{
    case Active = 1;
    case Closed = 2;
    case Blocked = 3;

    /**
     * @return array{code: string, name: string}
     */
    public function codeAndName(): array
    {
        return match ($this) {
            DealStatus::Active => [
                'code' => 'active',
                'name' => 'Active',
            ],
            DealStatus::Closed => [
                'code' => 'closed',
                'name' => 'Closed',
            ],
            DealStatus::Blocked => [
                'code' => 'blocked',
                'name' => 'Blocked',
            ],
        };
    }
}
