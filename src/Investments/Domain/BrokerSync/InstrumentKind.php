<?php

declare(strict_types=1);

namespace App\Investments\Domain\BrokerSync;

enum InstrumentKind: string
{
    case Share = 'share';
    case Etf = 'etf';
    case Bond = 'bond';
    case Future = 'future';
    case Currency = 'currency';
    case Other = 'other';
}
