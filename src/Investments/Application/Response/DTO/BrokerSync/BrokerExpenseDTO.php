<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\DTO\BrokerSync;

/**
 * @psalm-api
 */
final readonly class BrokerExpenseDTO
{
    public function __construct(
        public int $year,
        public string $type,
        public string $name,
        public string $amount,
    ) {
    }
}
