<?php

declare(strict_types=1);

namespace App\Investments\Domain\Analytics;

interface StatisticRepositoryInterface
{
    /**
     * @return list<array{balance: string, usd_balance: string, investments: string, current_value: string, profit: string, date: string}>
     */
    public function getLatestStatistic(): array;

    /**
     * @return list<array{balance: string, usd_balance: string, investments: string, current_value: string, profit: string, date: string}>
     */
    public function getStatisticByYears(): array;
}
