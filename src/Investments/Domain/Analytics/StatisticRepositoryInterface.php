<?php

declare(strict_types=1);

namespace App\Investments\Domain\Analytics;

interface StatisticRepositoryInterface
{
    /**
     * @param int $userId
     * @return list<array{balance: string, usd_balance: string, investments: string, current_value: string, profit: string, date: string}>
     */
    public function getLatestStatistic(int $userId): array;

    /**
     * @param int $userId
     * @return list<array{balance: string, usd_balance: string, investments: string, current_value: string, profit: string, date: string}>
     */
    public function getStatisticByYears(int $userId): array;
}
