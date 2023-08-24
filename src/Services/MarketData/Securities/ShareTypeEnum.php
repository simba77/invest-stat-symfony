<?php

declare(strict_types=1);

namespace App\Services\MarketData\Securities;

enum ShareTypeEnum: int
{
    case Stock = 1;
    case ETF = 2;
    case Share = 3;
}
