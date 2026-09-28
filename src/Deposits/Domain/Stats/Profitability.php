<?php

declare(strict_types=1);

namespace App\Deposits\Domain\Stats;

final readonly class Profitability
{
    public function __construct(
        /** @var numeric-string */
        public string $profitPercent,
        /** @var numeric-string */
        public string $annualizedPercent,
    ) {
    }

    public static function none(): self
    {
        return new self('0', '0');
    }
}
