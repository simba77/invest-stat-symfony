<?php

declare(strict_types=1);

namespace App\Investments\Domain\Tax;

use App\Shared\Domain\TaxProfile;

final readonly class TaxCalculator implements TaxCalculatorInterface
{
    #[\Override]
    public function calculateFromNet(string $netAmount, TaxProfile $taxProfile): TaxBreakdown
    {
        $ratePercent = $taxProfile->ratePercent();
        $roundedNetAmount = bcround($netAmount, 2);

        if (! $taxProfile->isTaxApplied()) {
            return new TaxBreakdown(
                gross: $roundedNetAmount,
                net: $roundedNetAmount,
                tax: '0.00',
                ratePercent: $ratePercent,
                isTaxApplied: false,
            );
        }

        $grossAmount = bcdiv(
            bcmul($netAmount, '100', 8),
            bcsub('100', $ratePercent, 8),
            8
        );
        $taxAmount = bcsub($grossAmount, $netAmount, 8);

        return new TaxBreakdown(
            gross: bcround($grossAmount, 2),
            net: $roundedNetAmount,
            tax: bcround($taxAmount, 2),
            ratePercent: $ratePercent,
            isTaxApplied: true,
        );
    }
}
