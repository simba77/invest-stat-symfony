<?php

declare(strict_types=1);

namespace App\Investments\Domain\BrokerSync\Client;

use App\Investments\Domain\BrokerSync\InstrumentKind;

final readonly class ExternalPosition
{
    public function __construct(
        public string $instrumentUid,
        public string $ticker,
        public ?InstrumentKind $kind,
        public int $quantity,
    ) {
    }
}
