<?php

declare(strict_types=1);

namespace App\Investments\Domain\BrokerSync;

enum BrokerProvider: string
{
    case TInvest = 'tinvest';

    public function title(): string
    {
        return match ($this) {
            self::TInvest => 'T-Bank',
        };
    }
}
