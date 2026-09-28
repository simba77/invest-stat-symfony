<?php

declare(strict_types=1);

namespace App\Deposits\Application\Response\DTO;

/**
 * @psalm-api
 */
final readonly class DepositAccountStatsDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $balance,
        public string $profit,
        public string $grossInvested,
        public string $profitPercent,
        public string $annualizedPercent,
    ) {
    }
}
