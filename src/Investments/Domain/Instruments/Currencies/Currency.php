<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments\Currencies;

enum Currency: string
{
    case RUB = 'RUB';
    case USD = 'USD';
    case EUR = 'EUR';

    public function symbol(): string
    {
        return match ($this) {
            self::RUB => '₽',
            self::USD => '$',
            self::EUR => '€',
        };
    }
}
