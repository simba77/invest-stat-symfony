<?php

declare(strict_types=1);

namespace App\Investments\Domain\BrokerSync\Ledger;

/**
 * Money the owner put into the account (positive) or took out of it (negative).
 */
final readonly class CashFlow
{
    /**
     * @param numeric-string $amount
     */
    public function __construct(
        public string $externalId,
        public \DateTimeImmutable $at,
        public string $amount,
        public string $currency,
    ) {
    }
}
