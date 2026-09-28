<?php

declare(strict_types=1);

namespace App\Deposits\Application\Response\DTO;

/**
 * @psalm-api
 */
final readonly class DepositStatsDTO
{
    public function __construct(
        public DepositStatsSummaryDTO $summary,
        /** @var list<DepositMonthlyStatsDTO> */
        public array $monthlyStats,
        /** @var list<DepositAccountStatsDTO> */
        public array $accounts,
    ) {
    }
}
