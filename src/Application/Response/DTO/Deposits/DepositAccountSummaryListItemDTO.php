<?php

declare(strict_types=1);

namespace App\Application\Response\DTO\Deposits;

class DepositAccountSummaryListItemDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public float $total,
        public float $profit,
    ) {
    }
}
