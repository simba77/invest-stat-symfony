<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments\Securities;

enum SecurityTypeEnum: int
{
    case Share = 1;
    case Bond = 2;
    case Future = 3;

    public function getCode(): string
    {
        return match ($this) {
            self::Share => 'share',
            self::Bond => 'bond',
            self::Future => 'future',
        };
    }
}
