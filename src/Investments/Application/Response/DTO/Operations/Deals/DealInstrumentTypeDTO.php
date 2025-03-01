<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\DTO\Operations\Deals;

/**
 * @psalm-api
 */
class DealInstrumentTypeDTO
{
    public function __construct(
        public string $code,
        public string $name,
    ) {
    }
}
