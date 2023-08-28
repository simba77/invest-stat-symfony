<?php

declare(strict_types=1);

namespace App\Services\Deals;

enum DealType: int
{
    case Long = 1;
    case Short = 2;
}
