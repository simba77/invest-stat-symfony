<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\DTO\Instruments;

final readonly class ShowShareDividendDTO
{
    public function __construct(
        public int $id,
        public string $date,
        public string $accountName,
        public string $amount,
        public string $tax,
    ) {
    }
}
