<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\DTO\Operations;

use App\Investments\Domain\Operations\Deals\GroupByTicker;

class FullPortfolioDTO
{
    public function __construct(
        /** @var array<string, array<string, array<string, array<string, GroupByTicker>>>> */
        public array $dealsList,
        /** @var array<string, array{code: string, name: string}> */
        public array $statuses,
        /** @var array<string, array{code: string, name: string}> */
        public array $instrumentTypes,
        /** @var array<string, array{code: string, name: string}> */
        public array $currencies,
        /** @var array<string, array<string, array<string, SummaryForGroupDTO>>> */
        public array $summary,
    ) {
    }
}
