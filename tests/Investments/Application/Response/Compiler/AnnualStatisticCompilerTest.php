<?php

declare(strict_types=1);

namespace App\Tests\Investments\Application\Response\Compiler;

use App\Investments\Application\Response\Compiler\AnnualStatisticCompiler;
use PHPUnit\Framework\TestCase;

class AnnualStatisticCompilerTest extends TestCase
{
    public function testCompileCalculatesModifiedDietzIncludingCurrentYearByLatestDate(): void
    {
        $compiler = new AnnualStatisticCompiler();

        $result = $compiler->compile(
            [
                'yearsData' => [
                    [
                        'balance' => '0.00',
                        'usd_balance' => '0.00',
                        'investments' => '1000.00',
                        'current_value' => '1000.00',
                        'profit' => '100.00',
                        'date' => '2022-01-01 00:00:00',
                    ],
                    [
                        'balance' => '0.00',
                        'usd_balance' => '0.00',
                        'investments' => '1100.00',
                        'current_value' => '1300.00',
                        'profit' => '160.00',
                        'date' => '2023-01-01 00:00:00',
                    ],
                    [
                        'balance' => '0.00',
                        'usd_balance' => '0.00',
                        'investments' => '1300.00',
                        'current_value' => '1600.00',
                        'profit' => '220.00',
                        'date' => '2024-01-01 00:00:00',
                    ],
                ],
                'latestData' => [
                    [
                        'balance' => '0.00',
                        'usd_balance' => '0.00',
                        'investments' => '1350.00',
                        'current_value' => '1700.00',
                        'profit' => '300.00',
                        'date' => '2024-12-31 00:00:00',
                    ],
                ],
                'cashFlows' => [
                    ['date' => '2022-07-02', 'sum' => '100.00'],
                    ['date' => '2024-03-01', 'sum' => '200.00'],
                ],
            ]
        );

        self::assertCount(3, $result);

        self::assertSame(2022, $result[0]['year']);
        self::assertSame(100.0, (float) $result[0]['profit']);
        self::assertSame(60.0, $result[0]['profitPercent']);
        self::assertSame(19.05, $result[0]['annualReturnPercent']);

        self::assertSame(2023, $result[1]['year']);
        self::assertSame(160.0, (float) $result[1]['profit']);
        self::assertSame(37.5, $result[1]['profitPercent']);
        self::assertSame(23.08, $result[1]['annualReturnPercent']);

        self::assertSame(2024, $result[2]['year']);
        self::assertSame(220.0, (float) $result[2]['profit']);
        self::assertSame(36.36, $result[2]['profitPercent']);
        self::assertSame(-5.66, $result[2]['annualReturnPercent']);
    }

    public function testCompileReturnsZeroAnnualReturnWhenDietzDenominatorIsNotPositive(): void
    {
        $compiler = new AnnualStatisticCompiler();

        $result = $compiler->compile(
            [
                'yearsData' => [
                    [
                        'balance' => '0.00',
                        'usd_balance' => '0.00',
                        'investments' => '100.00',
                        'current_value' => '0.00',
                        'profit' => '100.00',
                        'date' => '2022-01-01 00:00:00',
                    ],
                    [
                        'balance' => '0.00',
                        'usd_balance' => '0.00',
                        'investments' => '100.00',
                        'current_value' => '250.00',
                        'profit' => '250.00',
                        'date' => '2023-01-01 00:00:00',
                    ],
                ],
                'latestData' => [],
                'cashFlows' => [
                    ['date' => '2022-01-01', 'sum' => '-100.00'],
                ],
            ]
        );

        self::assertCount(1, $result);
        self::assertSame(150.0, $result[0]['profitPercent']);
        self::assertSame(0.0, $result[0]['annualReturnPercent']);
    }
}
