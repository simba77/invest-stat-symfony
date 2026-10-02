<?php

declare(strict_types=1);

namespace App\Investments\Domain\BrokerSync\Ledger;

/**
 * The state of an account rebuilt from its broker operations.
 */
final readonly class Ledger
{
    /** Trading days, payout dates and splits are counted in exchange time. */
    public const string TIMEZONE = 'Europe/Moscow';

    /**
     * @param list<Lot> $lots
     * @param list<Payout> $dividends
     * @param list<Payout> $coupons
     * @param list<CashFlow> $cashFlows
     * @param array<string, int> $positions signed quantity by instrument uid, without closed positions
     * @param array<string, InstrumentRef> $instruments by uid
     * @param array<string, numeric-string> $cash by currency
     * @param list<string> $warnings
     */
    public function __construct(
        public array $lots,
        public array $dividends,
        public array $coupons,
        public array $cashFlows,
        public array $positions,
        public array $instruments,
        public array $cash,
        public array $warnings,
    ) {
    }

    /**
     * The exchange date of a moment, at midnight.
     */
    public static function localDate(\DateTimeImmutable $moment): \DateTimeImmutable
    {
        return $moment->setTimezone(new \DateTimeZone(self::TIMEZONE))->setTime(0, 0);
    }
}
