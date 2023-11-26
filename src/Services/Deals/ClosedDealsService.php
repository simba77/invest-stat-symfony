<?php

declare(strict_types=1);

namespace App\Services\Deals;

use App\Entity\User;
use App\Repository\DealRepository;
use App\Request\DTO\Deals\DealsFilterRequestDTO;
use App\Services\MarketData\Currencies\CurrencyService;

class ClosedDealsService
{
    public function __construct(
        private readonly DealRepository $dealRepository,
        private readonly CurrencyService $currencyService,
    ) {
    }

    public function getDeals(User $user, ?DealsFilterRequestDTO $filterRequestDTO): array
    {
        $dealsByTickers = [];
        $deals = $this->dealRepository->getClosedDealsForUserByFilter($user);
        foreach ($deals as $deal) {
            $dealData = new DealData($deal, $deal['deal']->getAccount(), $this->currencyService);

            if (array_key_exists($dealData->getTicker(), $dealsByTickers)) {
                $group = $dealsByTickers[$dealData->getTicker()];
            } else {
                $group = new ClosedDealsGroupedByTicker();
                $dealsByTickers[$dealData->getTicker()] = $group;
            }

            $group->addDeal($dealData);
        }

        return $dealsByTickers;
    }
}
