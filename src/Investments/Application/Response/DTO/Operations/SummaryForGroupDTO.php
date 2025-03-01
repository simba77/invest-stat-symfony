<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\DTO\Operations;

/**
 * @psalm-api
 */
class SummaryForGroupDTO
{
    public function __construct(
        public string $buyPrice,
        public string $buyPriceInBaseCurrency,
        public string $currentPrice,
        public string $currentPriceInBaseCurrency,
        public string $profit,
        public string $profitInBaseCurrency,
        public string $profitPercent,
        public bool $isBaseCurrency,
    ) {
    }
}
