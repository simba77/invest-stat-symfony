<?php

declare(strict_types=1);

namespace App\Domain\Investments\Operations\Deals;

use App\Domain\Investments\Accounts\Account;
use App\Domain\Investments\Accounts\AccountCalculator;
use App\Domain\Investments\Accounts\AccountService;
use App\Domain\Investments\Instruments\Currencies\CurrencyService;
use App\Domain\Shared\User;
use App\Infrastructure\Persistence\Repository\DealRepository;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class DealsListService
{
    public function __construct(
        private readonly DealRepository $dealRepository,
        private readonly PropertyAccessorInterface $propertyAccess,
        private readonly AccountCalculator $accountCalculator,
        private readonly CurrencyService $currencyService,
        private readonly AccountService $accountService,
    ) {
    }

    public function getListWithGroups(User $user, Account $account): array
    {
        $result = [];
        $statuses = [];
        $instrumentTypes = [];
        $currencies = [];
        $deals = $this->dealRepository->findForUserAndAccount($user, $account);
        $summary = new SummaryForGroup();
        $accountValue = $this->accountCalculator->getAccountValue($account);

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

            $dealData = new DealData($deal, $account, $this->currencyService);

            /** @var ?GroupByTicker $group */
            $group = $this->propertyAccess->getValue($result, '[' . $status['code'] . '][' . $instrumentType['code'] . '][' . $currency['code'] . '][' . $ticker . ']');
            if ($group) {
                $group->addDeal($dealData);
            } else {
                $group = new GroupByTicker($accountValue);
                $group->addDeal($dealData);
                $result[$status['code']][$instrumentType['code']][$currency['code']][$ticker] = $group;
            }

            $summary->addDeal($status['code'], $instrumentType['code'], $currency['code'], $dealData);
        }

        return [
            'dealsList'       => $this->resortTickers($result),
            'statuses'        => $statuses,
            'instrumentTypes' => $instrumentTypes,
            'currencies'      => $currencies,
            'summary'         => $summary->getSummary(),
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

    private function resortTickers(array $result): array
    {
        foreach ($result as $statusCode => $groupByStatus) {
            foreach ($groupByStatus as $instrumentTypeCode => $groupByInstrumentType) {
                foreach ($groupByInstrumentType as $currencyCode => $groupByCurrency) {
                    usort($result[$statusCode][$instrumentTypeCode][$currencyCode], function (GroupByTicker $a, GroupByTicker $b) {
                        if ($a->getGroupData()->percent > $b->getGroupData()->percent) {
                            return -1;
                        } elseif ($a->getGroupData()->percent < $b->getGroupData()->percent) {
                            return 1;
                        }
                        return 0;
                    });
                }
            }
        }

        return $result;
    }

    public function getFullPortfolio(User $user): array
    {
        $result = [];
        $statuses = [];
        $instrumentTypes = [];
        $currencies = [];
        $deals = $this->dealRepository->findForUser($user);
        $summary = new SummaryForGroup();

        $accountsValue = '0';
        $accounts = $this->accountService->getAccountsListForUser($user);
        foreach ($accounts as $account) {
            $accountsValue = bcadd($accountsValue, $account->currentValue, 2);
        }

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

            $dealData = new DealData($deal, $deal['deal']->getAccount(), $this->currencyService);

            /** @var ?GroupByTicker $group */
            $group = $this->propertyAccess->getValue($result, '[' . $status['code'] . '][' . $instrumentType['code'] . '][' . $currency['code'] . '][' . $ticker . ']');
            if ($group) {
                $group->addDeal($dealData);
            } else {
                $group = new GroupByTicker($accountsValue);
                $group->addDeal($dealData);
                $result[$status['code']][$instrumentType['code']][$currency['code']][$ticker] = $group;
            }

            $summary->addDeal($status['code'], $instrumentType['code'], $currency['code'], $dealData);
        }

        return [
            'dealsList'       => $this->resortTickers($result),
            'statuses'        => $statuses,
            'instrumentTypes' => $instrumentTypes,
            'currencies'      => $currencies,
            'summary'         => $summary->getSummary(),
        ];
    }
}
