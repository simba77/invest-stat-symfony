<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\DTO\BrokerSync;

/**
 * @psalm-api
 */
final readonly class BrokerExpensesDTO
{
    /**
     * @param list<BrokerExpenseDTO> $items newest year first
     */
    public function __construct(
        public array $items,
        public string $total,
    ) {
    }
}
