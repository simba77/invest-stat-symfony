<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments\Currencies;

enum Currency: string
{
    case RUB = 'RUB';
    case USD = 'USD';
    case EUR = 'EUR';
    case CNY = 'CNY';
    case HKD = 'HKD';

    public function symbol(): string
    {
        return match ($this) {
            self::RUB => '₽',
            self::USD => '$',
            self::EUR => '€',
            self::CNY => '¥',
            self::HKD => 'HK$',
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::RUB => 'Russian Rouble',
            self::USD => 'US Dollar',
            self::EUR => 'Euro',
            self::CNY => 'Chinese Yuan',
            self::HKD => 'Hong Kong Dollar',
        };
    }

    /**
     * Instruments come in more currencies than listed here: the others are shown by their code.
     */
    public static function symbolOf(string $code): string
    {
        return self::tryFrom($code)?->symbol() ?? $code;
    }

    public static function titleOf(string $code): string
    {
        return self::tryFrom($code)?->title() ?? $code;
    }
}
