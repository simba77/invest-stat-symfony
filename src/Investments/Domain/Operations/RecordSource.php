<?php

declare(strict_types=1);

namespace App\Investments\Domain\Operations;

/**
 * Who keeps a record: the owner by hand, or the broker sync that rebuilds it on every run.
 */
enum RecordSource: int
{
    case Manual = 1;
    case Broker = 2;
}
