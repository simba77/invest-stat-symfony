<?php

declare(strict_types=1);

namespace App\Deposits\Application\Response\DTO;

/**
 * @psalm-api
 */
final readonly class DepositMonthlyStatsDTO
{
    public function __construct(
        public string $month,
        public string $deposits,
        public string $profit,
    ) {
    }
}
