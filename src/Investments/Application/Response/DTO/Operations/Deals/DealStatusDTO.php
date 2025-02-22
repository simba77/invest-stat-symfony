<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\DTO\Operations\Deals;

class DealStatusDTO
{
    public function __construct(
        public string $code,
        public string $name,
    ) {
    }
}
