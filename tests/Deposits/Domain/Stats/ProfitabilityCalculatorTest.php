<?php

declare(strict_types=1);

namespace App\Tests\Deposits\Domain\Stats;

use App\Deposits\Domain\Stats\Profitability;
use App\Deposits\Domain\Stats\ProfitabilityCalculator;
use PHPUnit\Framework\TestCase;

final class ProfitabilityCalculatorTest extends TestCase
{
    /**
     * @dataProvider accounts
     *
     * @param numeric-string $profit
     * @param numeric-string $grossInvested
     * @param array{string, string} $expected profit percent and annualized percent
     */
    public function testCalculatesAccountProfitability(string $profit, string $grossInvested, int $activeDays, array $expected): void
    {
        $profitability = (new ProfitabilityCalculator())->forAccount($profit, $grossInvested, $activeDays);

        self::assertSame($expected, $this->percents($profitability));
    }

    /**
     * @return iterable<string, array{string, string, int, array{string, string}}>
     */
    public static function accounts(): iterable
    {
        yield 'a full year' => ['50.00', '1000.00', 365, ['5.00', '5.00']];
        yield 'a short period is scaled to a year' => ['10.00', '1000.00', 73, ['1.00', '5.00']];
        yield 'a long period is scaled down to a year' => ['1000.00', '100000.00', 200, ['1.00', '1.82']];
        yield 'truncated, not rounded' => ['500.00', '60000.00', 150, ['0.83', '2.01']];
        yield 'loss' => ['-50.00', '1000.00', 365, ['-5.00', '-5.00']];
        yield 'nothing invested' => ['0.00', '0.00', 100, ['0', '0']];
    }

    /**
     * @dataProvider portfolios
     *
     * @param numeric-string $profit
     * @param numeric-string $grossInvested
     * @param list<int> $activeDaysPerAccount
     * @param array{string, string} $expected profit percent and annualized percent
     */
    public function testAnnualizesPortfolioOverAverageActiveDays(
        string $profit,
        string $grossInvested,
        array $activeDaysPerAccount,
        array $expected,
    ): void {
        $profitability = (new ProfitabilityCalculator())->forPortfolio($profit, $grossInvested, $activeDaysPerAccount);

        self::assertSame($expected, $this->percents($profitability));
    }

    /**
     * @return iterable<string, array{string, string, list<int>, array{string, string}}>
     */
    public static function portfolios(): iterable
    {
        yield 'average of two accounts' => ['1500.00', '160000.00', [200, 150], ['0.93', '1.93']];
        yield 'average is rounded half up' => ['100.00', '10000.00', [100, 101], ['1.00', '3.61']];
        yield 'nothing invested' => ['0', '0', [1], ['0', '0']];
        yield 'no accounts' => ['0', '0', [], ['0', '0']];
    }

    /**
     * @return array{string, string}
     */
    private function percents(Profitability $profitability): array
    {
        return [$profitability->profitPercent, $profitability->annualizedPercent];
    }
}
