<?php

declare(strict_types=1);

namespace App\Investments\Domain\Tax;

final readonly class TaxBreakdown
{
    public function __construct(
        public string $gross,
        public string $net,
        public string $tax,
        public string $ratePercent,
        public bool $isTaxApplied,
    ) {
    }
}
