<?php

declare(strict_types=1);

namespace App\Services\Deals;

use App\Entity\User;
use App\Repository\DealRepository;
use App\Repository\DividendRepository;
use App\Request\DTO\Deals\DealsFilterRequestDTO;
use App\Response\DTO\Deals\SummaryForClosedDealsDTO;
use App\Services\MarketData\Currencies\CurrencyService;

class ClosedDealsService
{
    public function __construct(
        private readonly DealRepository $dealRepository,
        private readonly CurrencyService $currencyService,
        private readonly DividendRepository $dividendRepository,
    ) {
    }

    public function getDeals(User $user, ?DealsFilterRequestDTO $filterRequestDTO): array
    {
        $dealsByTickers = [];
        $summaryBuyPrice = '0';
        $summarySellPrice = '0';
        $summaryProfit = '0';

        $deals = $this->dealRepository->getClosedDealsForUserByFilter($user, $filterRequestDTO);
        foreach ($deals as $deal) {
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

        return ['deals' => $dealsByTickers, 'summary' => $summary];
    }

    public function getMonthlyDealsStat(User $user, ?DealsFilterRequestDTO $filterRequestDTO): array
    {
        $profitByMonths = [];
        $deals = $this->dealRepository->getClosedDealsForUserByFilter($user, $filterRequestDTO);
        foreach ($deals as $deal) {
            $dealData = new DealData($deal, $deal['deal']->getAccount(), $this->currencyService);
            $date = $deal['deal']->getClosingDate()?->format('Y.m') ?? '0';

            if (isset($profitByMonths[$date])) {
                $profitByMonths[$date] = bcadd($profitByMonths[$date], $dealData->getProfitInBaseCurrency(), 2);
            } else {
                $profitByMonths[$date] = $dealData->getProfitInBaseCurrency();
            }
        }

        $dividends = $this->dividendRepository->findAll();
        foreach ($dividends as $dividend) {
            $date = $dividend->getDate()?->format('Y.m') ?? '0';
            if (isset($profitByMonths[$date])) {
                $profitByMonths[$date] = bcadd($profitByMonths[$date], $dividend->getAmount(), 2);
            } else {
                $profitByMonths[$date] = $dividend->getAmount();
            }
        }

        ksort($profitByMonths);

        return ['profitByMonths' => $profitByMonths];
    }
}
