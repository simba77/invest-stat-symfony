<?php

declare(strict_types=1);

namespace App\Investments\Domain\BrokerSync;

/**
 * How trade fees that the broker does not tie to a particular trade are spread over trades.
 */
enum FeeAllocation: string
{
    /** Fees are linked to their trade; unlinked ones are spread over the trades of their day. */
    case PerOperation = 'per_operation';

    /** All trade fees of a day are spread over the trades of that day by turnover. */
    case DailyProRata = 'daily_pro_rata';

    public function title(): string
    {
        return match ($this) {
            self::PerOperation => 'Per trade',
            self::DailyProRata => 'Daily total, split by turnover',
        };
    }
}
