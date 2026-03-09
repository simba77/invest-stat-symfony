<?php

declare(strict_types=1);

namespace App\Investments\Domain\Tax;

use App\Shared\Domain\TaxProfile;

interface TaxCalculatorInterface
{
    public function calculateFromNet(string $netAmount, TaxProfile $taxProfile): TaxBreakdown;
}
