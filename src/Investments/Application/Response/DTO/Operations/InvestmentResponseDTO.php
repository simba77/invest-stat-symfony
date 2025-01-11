<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\DTO\Operations;

class InvestmentResponseDTO
{
    public function __construct(
        public int $id,
        public string $sum,
        public string $date,
        public string $account,
        public string $currency = '₽'
    ) {
    }
}
