<?php

declare(strict_types=1);

namespace App\Domain\Investments\Operations\Deals;

enum DealType: int
{
    case Long = 1;
    case Short = 2;
}
