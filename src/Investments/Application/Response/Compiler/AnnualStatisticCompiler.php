<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\Compiler;

use App\Shared\Infrastructure\Compiler\CompilerInterface;
use Carbon\Carbon;

/**
 * @template-implements CompilerInterface<array{yearsData: array, latestData: array}, array>
 */
class AnnualStatisticCompiler implements CompilerInterface
{
    /**
     * @param array{
     *     yearsData: list<array{balance: string, usd_balance: string, investments: string, current_value: string, profit: string, date: string}>,
     *     latestData: list<array{balance: string, usd_balance: string, investments: string, current_value: string, profit: string, date: string}>
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
                'profitPercent' => 0,
            ];
        }

        foreach ($entry['latestData'] as $data) {
            if (isset($statByYears['current'])) {
                $existenceDate = $statByYears['current'];
            } else {
                $existenceDate = [
                    'year'          => 'current',
                    'date'          => $data['date'],
                    'balance'       => 0,
                    'usd_balance'   => 0,
                    'investments'   => 0,
                    'current_value' => 0,
                    'profit'        => 0,
                ];
            }

            $statByYears['current'] = [
                'year'          => 'current',
                'date'          => $data['date'],
                'balance'       => $existenceDate['balance'] + $data['balance'],
                'usd_balance'   => $existenceDate['usd_balance'] + $data['usd_balance'],
                'investments'   => $existenceDate['investments'] + $data['investments'],
                'current_value' => $existenceDate['current_value'] + $data['current_value'],
                'profit'        => $existenceDate['profit'] + $data['profit'],
                'profitPercent' => 0,
            ];
        }

        $prevKey = null;
        // Calculate profit
        foreach ($statByYears as $key => $statByYear) {
            if ($prevKey) {
                $statByYears[$prevKey]['profitPercent'] = round(($statByYear['profit'] * 100 / $statByYears[$prevKey]['profit']) - 100, 2);
            }
            $prevKey = $key;
        }

        unset($statByYears['current']);

        return $statByYears;
    }
}
