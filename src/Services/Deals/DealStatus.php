<?php

declare(strict_types=1);

namespace App\Services\Deals;

enum DealStatus: int
{
    case Active = 1;
    case Closed = 2;
    case Blocked = 3;
}
