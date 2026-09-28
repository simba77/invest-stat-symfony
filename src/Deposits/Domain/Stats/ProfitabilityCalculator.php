<?php

declare(strict_types=1);

namespace App\Deposits\Domain\Stats;

/**
 * Profit percent is the profit relative to the money put in; the annualized percent scales it
 * to a year of active days. Both are truncated to 2 decimals.
 */
final readonly class ProfitabilityCalculator
{
    private const string DAYS_IN_YEAR = '365';

    /**
     * @param numeric-string $profit
     * @param numeric-string $grossInvested
     */
    public function forAccount(string $profit, string $grossInvested, int $activeDays): Profitability
    {
        if (bccomp($grossInvested, '0', 2) <= 0) {
            return Profitability::none();
        }

        return $this->calculate($profit, $grossInvested, $activeDays);
    }

    /**
     * All accounts together are annualized over their average number of active days.
     *
     * @param numeric-string $profit
     * @param numeric-string $grossInvested
     * @param list<int> $activeDaysPerAccount
     */
    public function forPortfolio(string $profit, string $grossInvested, array $activeDaysPerAccount): Profitability
    {
        $totalActiveDays = array_sum($activeDaysPerAccount);
        if (bccomp($grossInvested, '0', 2) <= 0 || $totalActiveDays <= 0) {
            return Profitability::none();
        }

        $averageActiveDays = (int) round($totalActiveDays / count($activeDaysPerAccount));

        return $this->calculate($profit, $grossInvested, $averageActiveDays);
    }

    /**
     * @param numeric-string $profit
     * @param numeric-string $grossInvested
     */
    private function calculate(string $profit, string $grossInvested, int $activeDays): Profitability
    {
        $profitPercent = bcdiv(bcmul($profit, '100', 6), $grossInvested, 2);
        $annualizedPercent = bcdiv(bcmul($profitPercent, self::DAYS_IN_YEAR, 6), (string) max(1, $activeDays), 2);

        return new Profitability($profitPercent, $annualizedPercent);
    }
}
