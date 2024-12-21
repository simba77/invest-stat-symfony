<?php

declare(strict_types=1);

namespace App\Application\Response\DTO\Deposits;

class DepositAccountListItemDTO
{
    public function __construct(
        public int $id,
        public string $name
    ) {
    }
}
