<?php

declare(strict_types=1);

namespace App\Investments\Domain\Operations\Deals;

enum DealType: int
{
    case Long = 1;
    case Short = 2;
}
