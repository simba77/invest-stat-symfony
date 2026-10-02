<?php

declare(strict_types=1);

namespace App\Investments\Domain\BrokerSync\Client;

/**
 * What the broker says the account holds right now.
 */
final readonly class ExternalPositions
{
    /**
     * @param array<string, numeric-string> $money by currency code, blocked money included
     * @param list<ExternalPosition> $securities
     */
    public function __construct(
        public array $money,
        public array $securities,
    ) {
    }
}
