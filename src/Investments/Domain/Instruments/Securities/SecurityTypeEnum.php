<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments\Securities;

enum SecurityTypeEnum: int
{
    case Share = 1;
    case Bond = 2;
    case Future = 3;
}
