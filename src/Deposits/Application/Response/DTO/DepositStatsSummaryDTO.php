<?php

declare(strict_types=1);

namespace App\Deposits\Application\Response\DTO;

/**
 * @psalm-api
 */
final readonly class DepositStatsSummaryDTO
{
    public function __construct(
        public string $balance,
        public string $profit,
        public string $profitPercent,
        public string $annualizedPercent,
    ) {
    }
}
