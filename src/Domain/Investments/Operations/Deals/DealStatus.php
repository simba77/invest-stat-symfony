<?php

declare(strict_types=1);

namespace App\Domain\Investments\Operations\Deals;

enum DealStatus: int
{
    case Active = 1;
    case Closed = 2;
    case Blocked = 3;
}
