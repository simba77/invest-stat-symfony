<?php

declare(strict_types=1);

namespace App\Deposits\Application\Response\DTO;

/**
 * @psalm-api
 */
class DepositAccountEditFormDTO
{
    public function __construct(
        public int $id,
        public string $name
    ) {
    }
}
