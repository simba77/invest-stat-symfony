<?php

declare(strict_types=1);

namespace App\Tests\Investments\Infrastructure\Broker\TInvest;

use App\Investments\Infrastructure\Broker\TInvest\TInvestValues;
use Google\Protobuf\Timestamp;
use PHPUnit\Framework\TestCase;

final class TInvestValuesTest extends TestCase
{
    /**
     * @dataProvider decimals
     */
    public function testConvertsUnitsAndNanoWithoutRounding(int|string $units, int $nano, string $expected): void
    {
        self::assertSame($expected, TInvestValues::decimal($units, $nano));
    }

    /**
     * @return iterable<string, array{int|string, int, string}>
     */
    public static function decimals(): iterable
    {
        yield 'positive' => [524, 300000000, '524.300000000'];
        yield 'negative' => [-1, -710000000, '-1.710000000'];
        yield 'fraction only' => [0, -970000000, '-0.970000000'];
        yield 'string units' => ['2723', 230000000, '2723.230000000'];
        yield 'smallest step' => [0, 1, '0.000000001'];
    }

    public function testConvertsTimestampToApplicationTimezone(): void
    {
        $timestamp = new Timestamp();
        $timestamp->setSeconds(1712301309);
        $timestamp->setNanos(551478903);

        $dateTime = TInvestValues::dateTime($timestamp);

        self::assertSame('2024-04-05 07:15:09.551478', $dateTime->format('Y-m-d H:i:s.u'));
        self::assertSame(date_default_timezone_get(), $dateTime->getTimezone()->getName());
    }
}
