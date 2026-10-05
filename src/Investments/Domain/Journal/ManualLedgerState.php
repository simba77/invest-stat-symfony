<?php

declare(strict_types=1);

namespace App\Investments\Domain\Journal;

use App\Investments\Domain\BrokerSync\Ledger\LotBook;
use App\Investments\Domain\Instruments\Instrument;

/**
 * What a replay of a manual journal has built so far.
 *
 * @internal
 */
final class ManualLedgerState
{
    public LotBook $book;

    /** @var array<string, Instrument|null> by lot instrument uid */
    public array $instruments = [];

    /** @var array<string, numeric-string> by currency */
    public array $cash = [];

    /** @var array<string, true> lots opened so far, by key */
    public array $opened = [];

    /** @var array<string, list<ManualOperation>> operations waiting for their lot to open, by its key */
    public array $pending = [];

    /** @var array<string, true> */
    public array $knownCommissions = [];

    /** @var list<string> */
    public array $warnings = [];

    public function __construct()
    {
        $this->book = new LotBook();
    }
}
