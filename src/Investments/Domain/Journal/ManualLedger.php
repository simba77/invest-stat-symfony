<?php

declare(strict_types=1);

namespace App\Investments\Domain\Journal;

use App\Investments\Domain\BrokerSync\Ledger\Lot;
use App\Investments\Domain\Instruments\Instrument;

/**
 * The lots and the trading cash of a manual account rebuilt from its journal.
 */
final readonly class ManualLedger
{
    /**
     * @param list<Lot> $lots
     * @param array<string, Instrument|null> $instruments the catalogue instrument by lot instrument uid
     * @param array<string, numeric-string> $cash what trades and cash adjustments added, by currency
     * @param array<string, true> $knownCommissions the lots opened with a recorded commission, by the key of the opening
     * @param list<string> $warnings
     */
    public function __construct(
        public array $lots,
        public array $instruments,
        public array $cash,
        public array $knownCommissions,
        public array $warnings,
    ) {
    }
}
