<?php

declare(strict_types=1);

namespace App\Deposits\Domain\Stats;

/**
 * Counts the days an account balance was positive, so that the periods when all money
 * was withdrawn do not dilute the annualized profit.
 */
final readonly class ActiveDaysCalculator
{
    /**
     * @param list<array{sum: string, date: string}> $transactions sorted by date ascending
     * @return positive-int at least one day, the result is used as a divisor
     */
    public function calculate(array $transactions, \DateTimeImmutable $today): int
    {
        $balance = '0';
        $periodStart = null;
        $activeDays = 0;

        foreach ($transactions as $transaction) {
            $previousBalance = $balance;
            $balance = bcadd($balance, $transaction['sum'], 2);
            $date = new \DateTimeImmutable($transaction['date']);

            $becameActive = bccomp($previousBalance, '0', 2) <= 0 && bccomp($balance, '0', 2) > 0;
            if ($becameActive) {
                $periodStart = $date;
            }

            $becameIdle = bccomp($previousBalance, '0', 2) > 0 && bccomp($balance, '0', 2) <= 0;
            if ($becameIdle && $periodStart !== null) {
                $activeDays += (int) $date->diff($periodStart)->days;
                $periodStart = null;
            }
        }

        if ($periodStart !== null) {
            $activeDays += (int) $today->diff($periodStart)->days;
        }

        return max(1, $activeDays);
    }
}
