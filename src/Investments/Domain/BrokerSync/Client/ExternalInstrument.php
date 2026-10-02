<?php

declare(strict_types=1);

namespace App\Investments\Domain\BrokerSync\Client;

use App\Investments\Domain\BrokerSync\InstrumentKind;

final readonly class ExternalInstrument
{
    /**
     * @param numeric-string|null $nominal face value of one bond
     */
    public function __construct(
        public string $uid,
        public string $ticker,
        public string $classCode,
        public string $name,
        public ?InstrumentKind $kind,
        public string $currency,
        public int $lotSize,
        public ?string $isin,
        public ?string $nominal,
    ) {
    }
}
