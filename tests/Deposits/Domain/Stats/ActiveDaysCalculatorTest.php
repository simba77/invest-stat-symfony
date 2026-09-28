<?php

declare(strict_types=1);

namespace App\Tests\Deposits\Domain\Stats;

use App\Deposits\Domain\Stats\ActiveDaysCalculator;
use PHPUnit\Framework\TestCase;

final class ActiveDaysCalculatorTest extends TestCase
{
    private const string TODAY = '2025-06-30';

    /**
     * @dataProvider balanceHistories
     *
     * @param list<array{string, string}> $transactions sum and date
     */
    public function testCountsDaysWithPositiveBalance(array $transactions, int $expectedDays): void
    {
        $calculator = new ActiveDaysCalculator();

        $days = $calculator->calculate(
            array_map(static fn(array $transaction): array => ['sum' => $transaction[0], 'date' => $transaction[1]], $transactions),
            new \DateTimeImmutable(self::TODAY),
        );

        self::assertSame($expectedDays, $days);
    }

    /**
     * @return iterable<string, array{list<array{string, string}>, int}>
     */
    public static function balanceHistories(): iterable
    {
        yield 'still active' => [[['1000.00', '2025-06-20']], 10];
        yield 'partial withdrawal keeps it active' => [[['1000.00', '2025-06-01'], ['-400.00', '2025-06-15']], 29];
        yield 'withdrawn completely' => [[['1000.00', '2025-01-01'], ['-1000.00', '2025-01-31']], 30];
        yield 'idle period is skipped' => [
            [['1000.00', '2025-01-01'], ['-1000.00', '2025-01-31'], ['500.00', '2025-06-10']],
            30 + 20,
        ];
        yield 'overdrawn and topped up again' => [
            [['100.00', '2025-01-01'], ['-200.00', '2025-01-11'], ['300.00', '2025-06-20']],
            10 + 10,
        ];
        yield 'no transactions count as one day' => [[], 1];
        yield 'withdrawn on the deposit day counts as one day' => [[['1000.00', '2025-06-10'], ['-1000.00', '2025-06-10']], 1];
        yield 'never positive counts as one day' => [[['-100.00', '2025-06-01']], 1];
    }
}
