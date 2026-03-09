<?php

declare(strict_types=1);

namespace App\Shared\Domain;

enum TaxProfile: string
{
    case None = 'none';
    case Ndfl13 = 'ndfl_13';
    case Ndfl15 = 'ndfl_15';

    public function ratePercent(): string
    {
        return match ($this) {
            self::None => '0',
            self::Ndfl13 => '13',
            self::Ndfl15 => '15',
        };
    }

    public function isTaxApplied(): bool
    {
        return $this !== self::None;
    }
}
