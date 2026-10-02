<?php

declare(strict_types=1);

namespace App\Investments\Infrastructure\Broker\TInvest;

use Google\Protobuf\Timestamp;

/**
 * Converts T-Invest API scalar messages without losing precision.
 */
final class TInvestValues
{
    /**
     * A `Quotation` or `MoneyValue`: whole units plus billionths, both carrying the sign.
     *
     * @return numeric-string
     */
    public static function decimal(int|string $units, int $nano): string
    {
        return bcadd((string) $units, bcdiv((string) $nano, '1000000000', 9), 9);
    }

    public static function dateTime(Timestamp $timestamp): \DateTimeImmutable
    {
        $dateTime = \DateTimeImmutable::createFromFormat(
            'U.u',
            sprintf('%d.%06d', $timestamp->getSeconds(), intdiv($timestamp->getNanos(), 1000)),
        );
        if ($dateTime === false) {
            throw new \RuntimeException('Invalid timestamp');
        }

        return $dateTime->setTimezone(new \DateTimeZone(date_default_timezone_get()));
    }
}
