<?php

declare(strict_types=1);

namespace App\Application\Response\DTO\Accounts;

class AccountResponseDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public int $sort = 0
    ) {
    }
}
