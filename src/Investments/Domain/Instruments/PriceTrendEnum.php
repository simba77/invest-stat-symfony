<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments;

enum PriceTrendEnum: string
{
    case UP = 'up';
    case DOWN = 'down';
    case FLAT = 'flat';

    public static function fromPrices(string $current, string $previous): self
    {
        if (bccomp($current, $previous, 4) === 1) {
            return self::UP;
        }

        if (bccomp($current, $previous, 4) === -1) {
            return self::DOWN;
        }

        return self::FLAT;
    }
}
