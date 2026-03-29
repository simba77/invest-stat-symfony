<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\Compiler;

use App\Shared\Infrastructure\Compiler\CompilerInterface;
use Carbon\Carbon;

/**
 * @template-implements CompilerInterface<array{yearsData: array, latestData: array, cashFlows: array}, array>
 */
class AnnualStatisticCompiler implements CompilerInterface
{
    /**
     * @param array{
     *     yearsData: list<array{balance: string, usd_balance: string, investments: string, current_value: string, profit: string, date: string}>,
     *     latestData: list<array{balance: string, usd_balance: string, investments: string, current_value: string, profit: string, date: string}>,
     *     cashFlows: list<array{date: string, sum: string}>
     *         } $entry
     * @return array<int, mixed>
     */
    #[\Override]
    public function compile(mixed $entry): array
    {
        $statByYears = [];
        foreach ($entry['yearsData'] as $data) {
            $date = Carbon::createFromFormat('Y-m-d H:i:s', $data['date']);
            if (isset($statByYears[$date->year])) {
                $existenceDate = $statByYears[$date->year];
            } else {
                $existenceDate = [
                    'year'          => $date->year,
                    'date'          => $date,
                    'balance'       => 0,
                    'usd_balance'   => 0,
                    'investments'   => 0,
                    'current_value' => 0,
                    'profit'        => 0,
                ];
            }

            $statByYears[$date->year] = [
                'year'          => $date->year,
                'date'          => $date,
                'balance'       => $existenceDate['balance'] + $data['balance'],
                'usd_balance'   => $existenceDate['usd_balance'] + $data['usd_balance'],
                'investments'   => $existenceDate['investments'] + $data['investments'],
                'current_value' => $existenceDate['current_value'] + $data['current_value'],
                'profit'        => $existenceDate['profit'] + $data['profit'],
                'profitPercent' => 0.0,
                'annualReturnPercent' => 0.0,
            ];
        }

        $latestData = null;
        $latestDate = null;
        foreach ($entry['latestData'] as $data) {
            if ($latestData === null) {
                $latestData = [
                    'balance'       => 0.0,
                    'usd_balance'   => 0.0,
                    'investments'   => 0.0,
                    'current_value' => 0.0,
                    'profit'        => 0.0,
                ];
            }

            $latestPointDate = Carbon::createFromFormat('Y-m-d H:i:s', $data['date']);
            if ($latestDate === null || $latestPointDate->gt($latestDate)) {
                $latestDate = $latestPointDate;
            }

            $latestData['balance'] += (float) $data['balance'];
            $latestData['usd_balance'] += (float) $data['usd_balance'];
            $latestData['investments'] += (float) $data['investments'];
            $latestData['current_value'] += (float) $data['current_value'];
            $latestData['profit'] += (float) $data['profit'];
        }

        $cashFlows = $this->groupCashFlowsByDate($entry['cashFlows']);

        if (count($statByYears) < 1) {
            return [];
        }

        ksort($statByYears);
        $years = array_keys($statByYears);
        $result = [];

        foreach ($years as $index => $year) {
            $nextYear = $years[$index + 1] ?? null;
            if ($nextYear !== null) {
                $endPeriodData = $statByYears[$nextYear];
                $endDate = Carbon::create((int) $nextYear, 1, 1)->startOfDay();
            } elseif ($latestData !== null) {
                $endPeriodData = $latestData;
                $endDate = $latestDate;
            } else {
                continue;
            }

            if ($endDate === null) {
                continue;
            }

            $currentYearData = $statByYears[$year];
            $currentProfit = (float) $currentYearData['profit'];
            $nextProfit = (float) $endPeriodData['profit'];
            $startValue = (float) $currentYearData['current_value'];
            $endValue = (float) $endPeriodData['current_value'];
            $startDate = Carbon::create((int) $year, 1, 1)->startOfDay();

            if ($currentProfit !== 0.0) {
                $currentYearData['profitPercent'] = round((($nextProfit * 100.0) / $currentProfit) - 100.0, 2);
            }

            $currentYearData['annualReturnPercent'] = $this->calculateModifiedDietzReturn(
                $startValue,
                $endValue,
                $startDate,
                $endDate,
                $cashFlows
            );

            $result[] = $currentYearData;
        }

        return $result;
    }

    /**
     * @param list<array{date: string, sum: string}> $cashFlows
     * @return array<string, float>
     */
    private function groupCashFlowsByDate(array $cashFlows): array
    {
        $result = [];
        foreach ($cashFlows as $cashFlow) {
            if (! isset($result[$cashFlow['date']])) {
                $result[$cashFlow['date']] = 0.0;
            }

            $result[$cashFlow['date']] += (float) $cashFlow['sum'];
        }

        return $result;
    }

    /**
     * @param array<string, float> $cashFlowsByDate
     */
    private function calculateModifiedDietzReturn(
        float $startValue,
        float $endValue,
        Carbon $startDate,
        Carbon $endDate,
        array $cashFlowsByDate
    ): float {
        $periodDays = $startDate->diffInDays($endDate);
        if ($periodDays <= 0.0) {
            return 0.0;
        }

        $netCashFlow = 0.0;
        $weightedCashFlow = 0.0;

        foreach ($cashFlowsByDate as $date => $cashFlow) {
            $flowDate = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
            if ($flowDate->lt($startDate) || $flowDate->gt($endDate)) {
                continue;
            }

            $daysFromStart = $startDate->diffInDays($flowDate);
            $weight = ($periodDays - $daysFromStart) / $periodDays;
            if ($weight < 0.0) {
                $weight = 0.0;
            }

            $netCashFlow += $cashFlow;
            $weightedCashFlow += $weight * $cashFlow;
        }

        $denominator = $startValue + $weightedCashFlow;
        if ($denominator <= 0.0) {
            return 0.0;
        }

        return round((($endValue - $startValue - $netCashFlow) * 100.0) / $denominator, 2);
    }
}
