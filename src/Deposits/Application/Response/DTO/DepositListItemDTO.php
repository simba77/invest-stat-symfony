<?php

declare(strict_types=1);

namespace App\Deposits\Application\Response\DTO;

/**
 * @psalm-api
 */
class DepositListItemDTO
{
    public function __construct(
        public int $id,
        public string $date,
        public string $sum,
        public string $typeName,
        public string $accountName
    ) {
    }
}
