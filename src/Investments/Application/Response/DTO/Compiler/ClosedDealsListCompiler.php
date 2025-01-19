<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\DTO\Compiler;

use App\Investments\Application\Response\DTO\Analytics\SummaryForClosedDealsDTO;
use App\Investments\Domain\Instruments\Currencies\CurrencyService;
use App\Investments\Domain\Operations\Deals\ClosedDealsGroupedByTicker;
use App\Investments\Domain\Operations\Deals\DealData;
use App\Shared\Infrastructure\Compiler\CompilerInterface;

/**
 * @template-implements CompilerInterface<array, array{summary: SummaryForClosedDealsDTO, deals: ClosedDealsGroupedByTicker[]}>
 */
class ClosedDealsListCompiler implements CompilerInterface
{
    public function __construct(
        public readonly CurrencyService $currencyService,
    ) {
    }

    /**
     * @param mixed $entry
     * @return array{summary: SummaryForClosedDealsDTO, deals: ClosedDealsGroupedByTicker[]}
     */
    public function compile(mixed $entry): array
    {
        $dealsByTickers = [];
        $summaryBuyPrice = '0';
        $summarySellPrice = '0';
        $summaryProfit = '0';

        foreach ($entry as $deal) {
            $dealData = new DealData($deal, $deal['deal']->getAccount(), $this->currencyService);

            if (array_key_exists($dealData->getTicker(), $dealsByTickers)) {
                $group = $dealsByTickers[$dealData->getTicker()];
            } else {
                $group = new ClosedDealsGroupedByTicker();
                $dealsByTickers[$dealData->getTicker()] = $group;
            }

            $group->addDeal($dealData);

            $summaryBuyPrice = bcadd($summaryBuyPrice, $dealData->getFullBuyPriceInBaseCurrency(), 2);
            $summarySellPrice = bcadd($summarySellPrice, $dealData->getFullSellPriceInBaseCurrency(), 2);
            $summaryProfit = bcadd($summaryProfit, $dealData->getProfitInBaseCurrency(), 2);
        }

        if ($summaryBuyPrice > 0) {
            $summary = new SummaryForClosedDealsDTO(
                $summaryBuyPrice,
                $summarySellPrice,
                $summaryProfit,
                bcmul(bcdiv($summaryProfit, $summaryBuyPrice, 4), '100', 2)
            );
        } else {
            $summary = new SummaryForClosedDealsDTO('0', '0', '0', '0');
        }

        return ['deals' => array_values($dealsByTickers), 'summary' => $summary];
    }
}
