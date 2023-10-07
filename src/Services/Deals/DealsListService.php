<?php

declare(strict_types=1);

namespace App\Services\Deals;

use App\Entity\Account;
use App\Entity\User;
use App\Repository\DealRepository;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class DealsListService
{
    public function __construct(
        private readonly DealRepository $dealRepository,
        private readonly PropertyAccessorInterface $propertyAccess,
    ) {
    }

    public function getListWithGroups(User $user, Account $account): array
    {
        $result = [];
        $statuses = [];
        $instrumentTypes = [];
        $currencies = [];
        $deals = $this->dealRepository->findForUserAndAccount($user, $account);

        foreach ($deals as $deal) {
            // Statuses
            $status = $this->getStatus($deal);
            $statuses[$status['code']] = $status;

            // Type
            $instrumentType = $this->getInstrumentType($deal);
            $instrumentTypes[$instrumentType['code']] = $instrumentType;

            // Currency
            $currency = $this->getCurrency($deal);
            $currencies[$currency['code']] = $currency;

            $ticker = $deal['deal']->getTicker();

            /** @var ?GroupByTicker $group */
            $group = $this->propertyAccess->getValue($result, '[' . $status['code'] . '][' . $instrumentType['code'] . '][' . $currency['code'] . '][' . $ticker . ']');
            if ($group) {
                $group->addDeal(new DealCalculator($deal, $account));
            } else {
                $group = new GroupByTicker();
                $group->addDeal(new DealCalculator($deal, $account));
                $result[$status['code']][$instrumentType['code']][$currency['code']][$ticker] = $group;
            }
        }

        return [
            'dealsList'       => $result,
            'statuses'        => $statuses,
            'instrumentTypes' => $instrumentTypes,
            'currencies'      => $currencies,
        ];
    }

    private function getInstrumentType(array $deal): array
    {
        $type = match (true) {
            $deal['shareName'] !== null => 'shares',
            $deal['bondName'] !== null => 'bonds',
            $deal['futureName'] !== null => 'futures',
            default => 'other',
        };

        $types = [
            'shares'  => [
                'code' => 'shares',
                'name' => 'Shares',
            ],
            'bonds'   => [
                'code' => 'bonds',
                'name' => 'Bonds',
            ],
            'futures' => [
                'code' => 'futures',
                'name' => 'Futures',
            ],
            'other'   => [
                'code' => 'other',
                'name' => 'Other',
            ],
        ];

        return $types[$type];
    }

    private function getCurrency(array $deal): array
    {
        $currency = $deal['shareCurrency'] ?? $deal['bondCurrency'] ?? $deal['futureCurrency'] ?? 'RUB';
        $currencies = [
            'USD' => [
                'code' => 'USD',
                'name' => 'US Dollar',
            ],
            'RUB' => [
                'code' => 'RUB',
                'name' => 'Russian Rouble',
            ],
        ];

        return $currencies[$currency];
    }

    private function getStatus(array $deal): array
    {
        $status = $deal['deal']->getStatus();

        $statuses = [
            DealStatus::Active->name  => [
                'code' => 'active',
                'name' => 'Active',
            ],
            DealStatus::Closed->name  => [
                'code' => 'closed',
                'name' => 'Closed',
            ],
            DealStatus::Blocked->name => [
                'code' => 'blocked',
                'name' => 'Blocked',
            ],
        ];

        return $statuses[$status->name];
    }
}
