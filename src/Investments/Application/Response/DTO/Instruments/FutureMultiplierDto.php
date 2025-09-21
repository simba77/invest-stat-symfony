<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\DTO\Instruments;

class FutureMultiplierDto
{
    public function __construct(
        public int $id,
        public string $ticker,
        public string $value
    ) {
    }
}
