<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\DTO\Operations;

use App\Investments\Application\Response\DTO\Operations\Deals\DealCurrencyDTO;
use App\Investments\Application\Response\DTO\Operations\Deals\DealInstrumentTypeDTO;
use App\Investments\Application\Response\DTO\Operations\Deals\DealStatusDTO;
use App\Investments\Domain\Operations\Deals\GroupByTicker;

/**
 * @psalm-api
 */
class FullPortfolioDTO
{
    public function __construct(
        /** @var array<string, array<string, array<string, array<string, GroupByTicker>>>> */
        public array $dealsList,
        /** @var array<string, DealStatusDTO> */
        public array $statuses,
        /** @var array<string, DealInstrumentTypeDTO> */
        public array $instrumentTypes,
        /** @var array<string, DealCurrencyDTO> */
        public array $currencies,
        /** @var array<string, array<string, array<string, SummaryForGroupDTO>>> */
        public array $summary,
    ) {
    }
}
