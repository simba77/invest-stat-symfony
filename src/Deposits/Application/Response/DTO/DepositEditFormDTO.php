<?php

declare(strict_types=1);

namespace App\Deposits\Application\Response\DTO;

class DepositEditFormDTO
{
    public function __construct(
        public int $id,
        public string $sum,
        public int $type,
        public int $accountId,
        public string $date,
    ) {
    }
}
